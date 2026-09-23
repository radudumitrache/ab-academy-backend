<?php

namespace App\Services;

use App\Helpers\TimezoneHelper;
use App\Models\Event;
use App\Models\MeetingAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    /** Safety stop for list pagination, so a bad next_page_token cannot loop forever. */
    private const MAX_LIST_PAGES = 20;

    /**
     * Obtain a short-lived bearer token via Server-to-Server OAuth.
     *
     * Tokens are cached per account for just under their Zoom lifetime. Without the
     * cache a single account-selection pass re-requests a token for every list call,
     * which trips Zoom's rate limit on the token endpoint and makes perfectly healthy
     * accounts look unavailable.
     *
     * @param  bool  $fresh  Bypass the cache and store the newly issued token.
     */
    public function getAccessToken(MeetingAccount $account, bool $fresh = false): string
    {
        $cacheKey = $this->tokenCacheKey($account);

        if (!$fresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        Log::debug('Zoom token cache miss — requesting a new access token', [
            'account_id'   => $account->id,
            'account_name' => $account->name ?? null,
            'forced'       => $fresh,
        ]);

        $response = Http::withBasicAuth($account->client_id, $account->client_secret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $account->account_id,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom token request failed: ' . $response->body()
            );
        }

        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new \RuntimeException(
                'Zoom token response contained no access_token: ' . $response->body()
            );
        }

        // Let our copy lapse before Zoom expires its own (S2S tokens last 3599s).
        $ttl = max(60, (int) ($response->json('expires_in') ?: 3600) - 300);

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * Cache key for an account's access token.
     *
     * The credential fingerprint means an admin editing an account's Zoom credentials
     * automatically misses the cache, so there is no invalidation to remember on the
     * write path.
     */
    private function tokenCacheKey(MeetingAccount $account): string
    {
        return 'zoom_token:' . $account->id . ':' . sha1(
            $account->account_id . '|' . $account->client_id . '|' . $account->client_secret
        );
    }

    /**
     * Perform an authenticated Zoom API call, retrying once with a fresh token if
     * Zoom rejects the cached one.
     *
     * A cached token can stop working before it expires — the Zoom app is deactivated,
     * credentials are rotated, the token is revoked. Without this retry one stale cache
     * entry would take the account out of service for the whole TTL, which is a worse
     * outage than not caching at all.
     */
    private function request(MeetingAccount $account, string $method, string $url, array $payload): Response
    {
        $response = Http::withToken($this->getAccessToken($account))->{$method}($url, $payload);

        if ($response->status() === 401) {
            Log::debug('Zoom rejected the cached token — retrying with a fresh one', [
                'account_id'   => $account->id,
                'account_name' => $account->name ?? null,
            ]);

            $response = Http::withToken($this->getAccessToken($account, true))->{$method}($url, $payload);
        }

        return $response;
    }

    /**
     * Build the ISO-8601 UTC start time Zoom expects from an event's stored date + time.
     *
     * event_time is a TIME column, so MySQL hands it back as "HH:MM:SS" while SQLite
     * returns whatever was written ("HH:MM"). Normalising to HH:MM first keeps the
     * result valid on both.
     */
    private function startTimeFor(Event $event): string
    {
        return $event->event_date->format('Y-m-d') . 'T' . substr($event->event_time, 0, 5) . ':00';
    }

    /**
     * The duration to send to Zoom for an event, never longer than the configured cap.
     *
     * Validation keeps new events within the cap, but events stored before it existed
     * can still hold any value, and pushing one of those to Zoom would recreate exactly
     * the multi-day meetings that jammed account selection. Zoom does not end a meeting
     * when its scheduled duration elapses, so clamping this only affects the booking
     * length Zoom displays.
     */
    private function durationFor(Event $event): int
    {
        $cap      = (int) config('services.zoom.max_meeting_minutes', 240);
        $duration = (int) $event->event_duration;

        if ($cap > 0 && $duration > $cap) {
            Log::warning('Event duration exceeds the meeting cap — clamping what is sent to Zoom', [
                'event_id'         => $event->id,
                'raw_duration_min' => $duration,
                'capped_to_min'    => $cap,
            ]);

            return $cap;
        }

        return max(1, $duration);
    }

    /**
     * Create a scheduled Zoom meeting for the given event.
     * Returns ['meeting_id' => ..., 'join_url' => ..., 'start_url' => ...].
     */
    public function createMeeting(MeetingAccount $account, Event $event): array
    {
        $response = $this->request($account, 'post', 'https://api.zoom.us/v2/users/me/meetings', [
            'topic'      => $event->title,
            'type'       => 2, // scheduled
            'start_time' => $this->startTimeFor($event),
            'timezone'   => 'UTC', // start_time is always stored and sent as UTC
            'duration'   => $this->durationFor($event),
            'settings'   => [
                'join_before_host' => true,
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom meeting creation failed: ' . $response->body()
            );
        }

        return [
            'meeting_id' => (string) $response->json('id'),
            'join_url'   => $response->json('join_url'),
            'start_url'  => $response->json('start_url'),
        ];
    }

    /**
     * Fetch a single meeting, primarily to obtain a usable start_url.
     *
     * The start_url returned at creation embeds a ZAK token that expires about two
     * hours later, so for anything scheduled further ahead the stored link is dead by
     * the time the host needs it. This re-reads a fresh one.
     */
    public function getMeeting(MeetingAccount $account, string $meetingId): array
    {
        $response = $this->request($account, 'get', 'https://api.zoom.us/v2/meetings/' . $meetingId, []);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom meeting fetch failed: ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Push an event's current title, start time and duration onto its Zoom meeting.
     *
     * Without this a rescheduled event keeps a Zoom meeting at the old time: the link
     * points at the wrong slot, the old slot stays blocked, and the new one is left
     * unreserved.
     */
    public function updateMeeting(MeetingAccount $account, string $meetingId, Event $event): void
    {
        $response = $this->request($account, 'patch', 'https://api.zoom.us/v2/meetings/' . $meetingId, [
            'topic'      => $event->title,
            'start_time' => $this->startTimeFor($event),
            'timezone'   => 'UTC',
            'duration'   => $this->durationFor($event),
        ]);

        // 404 means the meeting is already gone from Zoom — nothing left to update.
        if (!$response->successful() && $response->status() !== 404) {
            throw new \RuntimeException(
                'Zoom meeting update failed: ' . $response->body()
            );
        }
    }

    /**
     * Delete a meeting from Zoom.
     *
     * Deleting an event without this leaves an orphaned Zoom meeting that keeps
     * blocking its slot on that account forever.
     */
    public function deleteMeeting(MeetingAccount $account, string $meetingId): void
    {
        $response = $this->request($account, 'delete', 'https://api.zoom.us/v2/meetings/' . $meetingId, []);

        // 404 means it is already deleted, which is the outcome we wanted anyway.
        if (!$response->successful() && $response->status() !== 404) {
            throw new \RuntimeException(
                'Zoom meeting deletion failed: ' . $response->body()
            );
        }
    }

    /**
     * Return the first Zoom meeting on this account that conflicts with the given
     * window, or null if the account is free.
     * Conflict window: [eventStart, eventStart + durationMinutes), padded by a buffer.
     *
     * Live (in-progress) meetings are weighed too, but only when they actually occupy
     * the host during the requested window — a meeting running right now says nothing
     * about a slot next week.
     */
    public function findOverlappingMeeting(MeetingAccount $account, \Carbon\Carbon $eventStart, int $durationMinutes): ?array
    {
        $bufferMinutes = (int) config('services.zoom.buffer_minutes', 20);
        $eventEnd      = $eventStart->copy()->addMinutes($durationMinutes);
        $now           = \Carbon\Carbon::now('UTC');

        foreach ($this->listLiveMeetings($account) as $live) {
            /*
             * Zoom's live list includes Personal Meeting Rooms and instant meetings that
             * report neither a start_time nor a duration — typically rooms someone opened
             * and never ended, carrying Zoom's default topic. There is no window to
             * reason about, and inventing a zero-width one at "now" made every such entry
             * block any slot within the changeover buffer of the present moment. Skip
             * them: what is known about them cannot place them in the requested window.
             *
             * The case worth catching still works, because it has both fields — a meeting
             * that has overrun its scheduled end is clamped to "now" below.
             */
            if (empty($live['start_time']) || empty($live['duration'])) {
                Log::debug('Zoom live meeting has no usable window — ignoring it', [
                    'account_id'      => $account->id,
                    'account_name'    => $account->name ?? null,
                    'zoom_meeting_id' => $live['id'] ?? null,
                    'live_topic'      => $live['topic'] ?? null,
                    'start_time'      => $live['start_time'] ?? null,
                    'duration'        => $live['duration'] ?? null,
                ]);
                continue;
            }

            $lStart = \Carbon\Carbon::parse($live['start_time'])->utc();
            $lEnd   = $this->occupiedUntil($lStart, (int) $live['duration'], $account, $live);

            // Still live past its scheduled end — it holds the host until at least now.
            if ($lEnd->lt($now)) {
                $lEnd = $now->copy();
            }

            if ($this->windowsConflict($lStart, $lEnd, $eventStart, $eventEnd, $bufferMinutes)) {
                Log::debug('Zoom live meeting conflicts with requested window', [
                    'account_id'   => $account->id,
                    'account_name' => $account->name ?? null,
                    'live_topic'   => $live['topic'] ?? null,
                    'live_start'   => $lStart->toIso8601String(),
                    'live_end'     => $lEnd->toIso8601String(),
                    'event_start'  => $eventStart->toIso8601String(),
                    'event_end'    => $eventEnd->toIso8601String(),
                ]);

                return $live + [
                    'conflict_source'        => 'live',
                    'effective_end'          => $lEnd->toIso8601String(),
                    'effective_duration_min' => $lStart->diffInMinutes($lEnd),
                ];
            }
        }

        $meetings = $this->listMeetings($account);

        Log::debug('Zoom overlap check', [
            'account_id'    => $account->id,
            'account_name'  => $account->name ?? null,
            'event_start'   => $eventStart->toIso8601String(),
            'event_end'     => $eventEnd->toIso8601String(),
            'buffer_minutes' => $bufferMinutes,
            'meeting_count' => count($meetings),
        ]);

        foreach ($meetings as $meeting) {
            if (empty($meeting['start_time']) || empty($meeting['duration'])) {
                continue;
            }
            $mStart          = \Carbon\Carbon::parse($meeting['start_time'])->utc();
            $mEnd            = $this->occupiedUntil($mStart, (int) $meeting['duration'], $account, $meeting);
            $mEndWithBuffer  = $mEnd->copy()->addMinutes($bufferMinutes);

            // Conflict if the existing meeting (plus buffer) overlaps the new event window.
            if ($this->windowsConflict($mStart, $mEnd, $eventStart, $eventEnd, $bufferMinutes)) {
                Log::debug('Zoom overlap found', [
                    'account_id'             => $account->id,
                    'conflicting_topic'      => $meeting['topic'] ?? null,
                    'conflicting_start'      => $mStart->toIso8601String(),
                    'conflicting_end'        => $mEnd->toIso8601String(),
                    'conflicting_end_buffer' => $mEndWithBuffer->toIso8601String(),
                    'raw_duration_min'       => (int) $meeting['duration'],
                ]);

                // Hand back the effective window alongside the raw meeting, so the
                // caller can report why this counted as a conflict.
                return $meeting + [
                    'conflict_source'        => 'scheduled',
                    'effective_end'          => $mEnd->toIso8601String(),
                    'effective_duration_min' => $mStart->diffInMinutes($mEnd),
                ];
            }
        }

        return null;
    }

    /**
     * How long an existing meeting should be treated as occupying its account.
     *
     * Zoom stores durations well beyond its own 1440-minute maximum, and these accounts
     * hold meetings created with placeholder lengths of 9999, 99999 and 999999 minutes
     * — 7, 69 and 694 days. Trusting those literally made a single stale meeting block
     * its account for months, so every account reported busy for every slot.
     *
     * A meeting claiming to run for days is not a real call of that length, so the
     * window is capped. Meetings at or under the cap are returned unchanged.
     */
    private function occupiedUntil(
        \Carbon\Carbon $start,
        int $durationMinutes,
        MeetingAccount $account,
        array $meeting
    ): \Carbon\Carbon {
        $cap = (int) config('services.zoom.max_meeting_minutes', 1440);

        if ($cap > 0 && $durationMinutes > $cap) {
            Log::warning('Zoom meeting reports an implausible duration — capping it for conflict detection', [
                'account_id'      => $account->id,
                'account_name'    => $account->name ?? null,
                'zoom_meeting_id' => $meeting['id'] ?? null,
                'topic'           => $meeting['topic'] ?? null,
                'type'            => $meeting['type'] ?? null,
                'start_time'      => $meeting['start_time'] ?? null,
                'raw_duration_min' => $durationMinutes,
                'capped_to_min'   => $cap,
            ]);

            $durationMinutes = $cap;
        }

        return $start->copy()->addMinutes(max(0, $durationMinutes));
    }

    /**
     * Shared conflict test: does an existing meeting's window collide with the
     * requested event window, allowing for the changeover buffer?
     *
     * The buffer applies only after the existing meeting ends. It used to be added to
     * the requested event's end as well, which silently demanded a gap on both sides
     * and made an account look busy for a slot that merely finished shortly before an
     * existing booking.
     */
    private function windowsConflict(
        \Carbon\Carbon $mStart,
        \Carbon\Carbon $mEnd,
        \Carbon\Carbon $eventStart,
        \Carbon\Carbon $eventEnd,
        int $bufferMinutes
    ): bool {
        return $mStart->lt($eventEnd)
            && $mEnd->copy()->addMinutes($bufferMinutes)->gt($eventStart);
    }

    /**
     * The event's stored date + time as a UTC Carbon instance.
     * Events are always stored in UTC — see the convert_existing_events_to_utc migration.
     */
    public function eventStartUtc(Event $event): \Carbon\Carbon
    {
        return TimezoneHelper::toUtc($event->event_date->format('Y-m-d'), $event->event_time, 'UTC');
    }

    /**
     * Walk the active accounts in order and stop at the first one free for this window.
     *
     * Busy accounts and unreachable accounts are tracked separately, because they are
     * different answers: a full pool means "pick another slot", while an unreachable
     * pool means "we do not know yet, retry". Folding the two together is what made a
     * rate-limited account look like a fully booked one.
     *
     * @param  callable|null  $onPick  Run against the chosen account while still inside
     *                                 the try block, so a failure there records an error
     *                                 and moves on to the next candidate.
     * @return array{account: ?MeetingAccount, result: mixed, busy: array, errors: array, total: int}
     */
    private function pickAccount(\Carbon\Carbon $eventStart, int $durationMinutes, ?callable $onPick = null): array
    {
        $accounts = MeetingAccount::where('is_active', true)->get();
        $busy     = [];
        $errors   = [];

        foreach ($accounts as $candidate) {
            try {
                $conflict = $this->findOverlappingMeeting($candidate, $eventStart, $durationMinutes);

                if ($conflict !== null) {
                    $busy[] = [
                        'account_id'               => $candidate->id,
                        'account_name'             => $candidate->name ?? null,
                        'conflicting_topic'        => $conflict['topic'] ?? null,
                        'conflicting_start'        => $conflict['start_time'] ?? null,
                        'conflicting_duration_min' => $conflict['duration'] ?? null,
                        // The window actually used, which differs from the raw duration
                        // when an implausible one had to be capped.
                        'conflicting_end'          => $conflict['effective_end'] ?? null,
                        'effective_duration_min'   => $conflict['effective_duration_min'] ?? null,
                        // Whether an in-progress or a scheduled meeting flagged this.
                        'conflict_source'          => $conflict['conflict_source'] ?? null,
                    ];
                    continue;
                }

                return [
                    'account' => $candidate,
                    'result'  => $onPick ? $onPick($candidate) : null,
                    'busy'    => $busy,
                    'errors'  => $errors,
                    'total'   => $accounts->count(),
                ];
            } catch (\Throwable $e) {
                Log::warning('Zoom account unavailable during account selection', [
                    'account_id'   => $candidate->id,
                    'account_name' => $candidate->name ?? null,
                    'error'        => $e->getMessage(),
                ]);

                $errors[] = [
                    'account_id'   => $candidate->id,
                    'account_name' => $candidate->name ?? null,
                    'error'        => $e->getMessage(),
                ];
            }
        }

        return [
            'account' => null,
            'result'  => null,
            'busy'    => $busy,
            'errors'  => $errors,
            'total'   => $accounts->count(),
        ];
    }

    /**
     * Which active account would be chosen for this window, without booking anything.
     *
     * @return array{account: ?MeetingAccount, busy: array, errors: array, total: int}
     */
    public function selectAvailableAccount(\Carbon\Carbon $eventStart, int $durationMinutes): array
    {
        $outcome = $this->pickAccount($eventStart, $durationMinutes);
        unset($outcome['result']);

        return $outcome;
    }

    /**
     * Select a free account and create this event's Zoom meeting on it.
     *
     * @return array{account: ?MeetingAccount, meeting: ?array, busy: array, errors: array, total: int}
     */
    public function bookMeetingFor(Event $event): array
    {
        $outcome = $this->pickAccount(
            $this->eventStartUtc($event),
            (int) $event->event_duration,
            fn (MeetingAccount $account) => $this->createMeeting($account, $event)
        );

        $outcome['meeting'] = $outcome['result'];
        unset($outcome['result']);

        return $outcome;
    }

    /**
     * Return true if this account already has a Zoom meeting that overlaps the
     * given UTC start time + duration window.
     *
     * @deprecated use findOverlappingMeeting() for richer diagnostics
     */
    public function hasOverlappingMeeting(MeetingAccount $account, \Carbon\Carbon $eventStart, int $durationMinutes): bool
    {
        return $this->findOverlappingMeeting($account, $eventStart, $durationMinutes) !== null;
    }

    /**
     * Fetch all scheduled meetings for the account from the Zoom API.
     * Returns the raw meetings array.
     */
    public function listMeetings(MeetingAccount $account): array
    {
        return $this->listMeetingsOfType($account, 'scheduled', 'Zoom meeting list failed');
    }

    /**
     * Fetch all currently live (in-progress) meetings for the account from the Zoom API.
     */
    public function listLiveMeetings(MeetingAccount $account): array
    {
        return $this->listMeetingsOfType($account, 'live', 'Zoom live meeting list failed');
    }

    /**
     * Fetch every page of one meeting list type.
     *
     * Zoom's "scheduled" type also returns past unexpired meetings, so a busy account
     * accumulates roughly a month of history alongside its future bookings and passes
     * 300 entries. Reading only the first page silently hid the conflicts on later
     * pages and let the same slot be double-booked.
     */
    private function listMeetingsOfType(MeetingAccount $account, string $type, string $errorPrefix): array
    {
        $meetings  = [];
        $pageToken = '';
        $pages     = 0;

        do {
            $query = [
                'type'      => $type,
                'page_size' => 300,
            ];

            if ($pageToken !== '') {
                $query['next_page_token'] = $pageToken;
            }

            $response = $this->request($account, 'get', 'https://api.zoom.us/v2/users/me/meetings', $query);

            if (!$response->successful()) {
                throw new \RuntimeException($errorPrefix . ': ' . $response->body());
            }

            $meetings  = array_merge($meetings, $response->json('meetings') ?? []);
            $pageToken = (string) ($response->json('next_page_token') ?? '');
            $pages++;
        } while ($pageToken !== '' && $pages < self::MAX_LIST_PAGES);

        if ($pageToken !== '') {
            Log::warning('Zoom meeting list truncated — more pages than expected', [
                'account_id'   => $account->id,
                'account_name' => $account->name ?? null,
                'type'         => $type,
                'pages_read'   => $pages,
                'meetings'     => count($meetings),
            ]);
        }

        return $meetings;
    }
}
