<?php

namespace App\Services;

use App\Helpers\TimezoneHelper;
use App\Models\Event;
use App\Models\MeetingAccount;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    /** Safety stop for list pagination, so a bad next_page_token cannot loop forever. */
    private const MAX_LIST_PAGES = 20;

    /** Seconds to wait for Zoom before giving up on one call. */
    private const HTTP_TIMEOUT = 20;
    private const HTTP_CONNECT_TIMEOUT = 5;

    /** Attempts per call and the pause between them, in milliseconds. */
    private const HTTP_TRIES = 3;
    private const HTTP_RETRY_DELAY_MS = 600;

    /** Booking lock: how long it may be held, and how long a request waits for it. */
    private const BOOKING_LOCK_SECONDS = 120;
    private const BOOKING_LOCK_WAIT_SECONDS = 25;

    /**
     * Meeting lists already fetched by this instance, keyed "<account id>:<type>".
     *
     * One request can ask about the same account many times (the recurrence endpoint
     * books a whole month of copies), and each ask costs a live list plus a scheduled
     * list per account. Entries are dropped whenever this instance changes an account's
     * meetings, so a booking is never invisible to the next check in the same request.
     */
    private array $listCache = [];

    /**
     * An HTTP client with timeouts and retries for transient Zoom failures.
     *
     * Without a timeout one hung connection stalls the whole account-selection pass,
     * which walks every account in turn. Rate limits (429) and 5xx responses are
     * retried; connection errors are retried only when the call is safe to repeat,
     * because a create that timed out may still have succeeded on Zoom's side and
     * repeating it would book a second meeting.
     */
    private function client(bool $repeatable = true): PendingRequest
    {
        return Http::timeout(self::HTTP_TIMEOUT)
            ->connectTimeout(self::HTTP_CONNECT_TIMEOUT)
            ->retry(
                self::HTTP_TRIES,
                self::HTTP_RETRY_DELAY_MS,
                function ($exception) use ($repeatable) {
                    if ($exception instanceof ConnectionException) {
                        return $repeatable;
                    }

                    if ($exception instanceof RequestException) {
                        $status = $exception->response->status();

                        return $status === 429 || ($repeatable && $status >= 500);
                    }

                    return false;
                },
                false // hand back the last response instead of throwing; callers inspect it
            );
    }

    /** Parse a timestamp Zoom sent, or null when it is missing or unreadable. */
    private function parseZoomTime(mixed $value): ?\Carbon\Carbon
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function forgetLists(MeetingAccount $account): void
    {
        unset($this->listCache[$account->id . ':live'], $this->listCache[$account->id . ':scheduled']);
    }

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

        $response = $this->client()
            ->withBasicAuth($account->client_id, $account->client_secret)
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
        // A POST creates something, so a dropped connection must not be repeated blindly.
        $repeatable = strtolower($method) !== 'post';

        $response = $this->client($repeatable)->withToken($this->getAccessToken($account))->{$method}($url, $payload);

        if ($response->status() === 401) {
            Log::debug('Zoom rejected the cached token — retrying with a fresh one', [
                'account_id'   => $account->id,
                'account_name' => $account->name ?? null,
            ]);

            $response = $this->client($repeatable)->withToken($this->getAccessToken($account, true))->{$method}($url, $payload);
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
    private function startTimeFor(Event $event, ?\Carbon\Carbon $notBefore = null): string
    {
        $scheduled = $event->event_date->format('Y-m-d') . 'T' . substr($event->event_time, 0, 5) . ':00';

        if ($notBefore === null) {
            return $scheduled;
        }

        // A meeting booked after its event's start time is scheduled from now instead.
        // Left in the past, it would be ignored as one that never started, and the next
        // booking could take the same account while its host is about to open it.
        return \Carbon\Carbon::parse($scheduled, 'UTC')->lt($notBefore)
            ? $notBefore->copy()->utc()->format('Y-m-d\TH:i:s')
            : $scheduled;
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
    public function createMeeting(MeetingAccount $account, Event $event, ?\Carbon\Carbon $notBefore = null): array
    {
        $response = $this->request($account, 'post', 'https://api.zoom.us/v2/users/me/meetings', [
            'topic'      => $event->title,
            'type'       => 2, // scheduled
            'start_time' => $this->startTimeFor($event, $notBefore),
            'timezone'   => 'UTC', // start_time is always stored and sent as UTC
            'duration'   => $this->durationFor($event),
            'settings'   => [
                'join_before_host' => true,
            ],
        ]);

        // Whatever happened, this account's meetings may have changed.
        $this->forgetLists($account);

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
                'Zoom meeting fetch failed: ' . $response->body(),
                $response->status()
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

        $this->forgetLists($account);

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

        $this->forgetLists($account);

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
     *
     * @param  string|null  $ignoreMeetingId  A Zoom meeting id to disregard, so an event's
     *                                        own meeting never conflicts with itself.
     */
    public function findOverlappingMeeting(
        MeetingAccount $account,
        \Carbon\Carbon $eventStart,
        int $durationMinutes,
        ?string $ignoreMeetingId = null
    ): ?array {
        $bufferMinutes = (int) config('services.zoom.buffer_minutes', 20);
        $eventEnd      = $eventStart->copy()->addMinutes(max(1, $durationMinutes));
        $now           = \Carbon\Carbon::now('UTC');
        $liveIds       = [];

        foreach ($this->listLiveMeetings($account) as $live) {
            $liveId = isset($live['id']) ? (string) $live['id'] : null;

            if ($liveId !== null) {
                $liveIds[$liveId] = true;
            }

            if ($ignoreMeetingId !== null && $liveId === $ignoreMeetingId) {
                continue;
            }

            $lStart    = $this->parseZoomTime($live['start_time'] ?? null);
            $lDuration = (int) ($live['duration'] ?? 0);

            /*
             * Zoom's live list includes Personal Meeting Rooms and instant meetings that
             * report neither a start_time nor a duration — typically rooms someone opened
             * and never ended. They still hold the host: Zoom refuses to start another
             * meeting on the account while one is live, which is what teachers hit when
             * the account was booked anyway.
             *
             * With no window to go on, all that is known is that the host is busy right
             * now, so it only blocks events about to start. Assuming it lasts as long as
             * a full meeting (the cap, 4h) made a single open room take an account out of
             * service for the whole afternoon, and with a few such rooms around every
             * account looked busy and teachers could not book at all.
             */
            if ($lStart === null || $lDuration <= 0) {
                $lStart = $now->copy();
                $lEnd   = $now->copy()->addMinutes((int) config('services.zoom.live_unknown_window_minutes', 30));

                Log::debug('Zoom live meeting has no usable window — treating it as occupying the account for a short window from now', [
                    'account_id'      => $account->id,
                    'account_name'    => $account->name ?? null,
                    'zoom_meeting_id' => $live['id'] ?? null,
                    'live_topic'      => $live['topic'] ?? null,
                    'start_time'      => $live['start_time'] ?? null,
                    'duration'        => $live['duration'] ?? null,
                ]);
            } else {
                $lEnd = $this->occupiedUntil($lStart, $lDuration, $account, $live);

                // Still live past its scheduled end — it holds the host until at least now.
                if ($lEnd->lt($now)) {
                    $lEnd = $now->copy();
                }
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
        $grace    = (int) config('services.zoom.no_show_grace_minutes', 15);

        Log::debug('Zoom overlap check', [
            'account_id'    => $account->id,
            'account_name'  => $account->name ?? null,
            'event_start'   => $eventStart->toIso8601String(),
            'event_end'     => $eventEnd->toIso8601String(),
            'buffer_minutes' => $bufferMinutes,
            'meeting_count' => count($meetings),
        ]);

        foreach ($meetings as $meeting) {
            $meetingId = isset($meeting['id']) ? (string) $meeting['id'] : null;

            // The ignored meeting, and live ones (already weighed above with their live window).
            if ($meetingId !== null && ($meetingId === $ignoreMeetingId || isset($liveIds[$meetingId]))) {
                continue;
            }

            $mStart    = $this->parseZoomTime($meeting['start_time'] ?? null);
            $mDuration = (int) ($meeting['duration'] ?? 0);

            if ($mStart === null || $mDuration <= 0) {
                continue;
            }

            /*
             * Zoom's "scheduled" list also returns meetings from the past month, and it
             * knows nothing about whether they actually ran. A meeting that began more
             * than the grace period ago and is not live has ended, been abandoned, or
             * never started — either way it is not holding the host. Counting its full
             * scheduled length made an account look busy for hours after a class that
             * finished early, which is a common way for free accounts to look booked.
             */
            if ($mStart->copy()->addMinutes($grace)->lt($now)) {
                continue;
            }

            $mEnd           = $this->occupiedUntil($mStart, $mDuration, $account, $meeting);
            $mEndWithBuffer = $mEnd->copy()->addMinutes($bufferMinutes);

            // Conflict if the existing meeting (plus buffer) overlaps the new event window.
            if ($this->windowsConflict($mStart, $mEnd, $eventStart, $eventEnd, $bufferMinutes)) {
                Log::debug('Zoom overlap found', [
                    'account_id'             => $account->id,
                    'conflicting_topic'      => $meeting['topic'] ?? null,
                    'conflicting_start'      => $mStart->toIso8601String(),
                    'conflicting_end'        => $mEnd->toIso8601String(),
                    'conflicting_end_buffer' => $mEndWithBuffer->toIso8601String(),
                    'raw_duration_min'       => $mDuration,
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
     * A live meeting on this account other than the given one, or null if the host is
     * free to start it.
     *
     * Zoom refuses to start a meeting while its host is in another one, and the start
     * link only fails once the teacher has clicked it. Callers use this when handing out
     * a start link so they can say why it will not work.
     */
    public function liveMeetingBlockingStart(MeetingAccount $account, string $meetingId): ?array
    {
        return $this->otherLiveMeeting($this->currentLiveMeetings($account), $meetingId);
    }

    /** The account's live meetings right now, always read fresh from Zoom. */
    public function currentLiveMeetings(MeetingAccount $account): array
    {
        $this->forgetLists($account);

        return $this->listLiveMeetings($account);
    }

    /** The first live meeting that is not the given one, or null. */
    public function otherLiveMeeting(array $liveMeetings, string $meetingId): ?array
    {
        foreach ($liveMeetings as $live) {
            if ((string) ($live['id'] ?? '') !== $meetingId) {
                return $live;
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
     * Walk the active accounts in order and pick one that is free for this window.
     *
     * Busy accounts and unreachable accounts are tracked separately, because they are
     * different answers: a full pool means "pick another slot", while an unreachable
     * pool means "we do not know yet, retry". Folding the two together is what made a
     * rate-limited account look like a fully booked one.
     *
     * Free for the window is not the whole story. A host account can run one meeting at
     * a time, and teachers open the host link as soon as the meeting is created — often
     * well before the class. An account that is free at 14:30 but in the middle of
     * another class at 12:52 would be picked, and Zoom then refuses to start the new
     * meeting ("You have a meeting that is currently in progress"). So accounts that are
     * also idle right now are preferred; ones in a meeting now are only used when no
     * idle account is left, in the same id order.
     *
     * @param  callable|null  $onPick  Run against the chosen account while still inside
     *                                 the try block, so a failure there records an error
     *                                 and moves on to the next candidate.
     * @param  string|null  $ignoreMeetingId  A meeting that must not count as a conflict (the
     *                                        event's own meeting, which is being replaced).
     * @return array{account: ?MeetingAccount, result: mixed, busy: array, errors: array, total: int, skipped_live_now: array, used_live_now_fallback: bool}
     */
    private function pickAccount(\Carbon\Carbon $eventStart, int $durationMinutes, ?callable $onPick = null, ?string $ignoreMeetingId = null): array
    {
        // Fixed order, so the same account is preferred every time rather than whichever
        // row the database happens to return first.
        $accounts = MeetingAccount::where('is_active', true)->orderBy('id')->get();
        $busy     = [];
        $errors   = [];
        $inMeetingNow = [];   // free for the window, but hosting a meeting right now

        // By reference: the lists keep growing after this is defined, and an arrow
        // function would freeze them as they are now (all empty).
        $outcome = function (?MeetingAccount $account, mixed $result, bool $fallback) use (&$busy, &$errors, &$inMeetingNow, $accounts) {
            return [
                'account'                => $account,
                'result'                 => $result,
                'busy'                   => $busy,
                'errors'                 => $errors,
                'total'                  => $accounts->count(),
                // Accounts passed over because they were in a meeting at this moment.
                'skipped_live_now'       => array_values(array_map(fn ($row) => $row['info'], $inMeetingNow)),
                'used_live_now_fallback' => $fallback,
            ];
        };

        foreach ($accounts as $candidate) {
            try {
                $conflict = $this->findOverlappingMeeting($candidate, $eventStart, $durationMinutes, $ignoreMeetingId);

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

                // Free for the window. Is the host in a meeting right now? The live list
                // was just read by the overlap check, so this costs no extra Zoom call.
                $live = $this->listLiveMeetings($candidate);

                if ($live !== []) {
                    $inMeetingNow[] = [
                        'account' => $candidate,
                        'info'    => [
                            'account_id'   => $candidate->id,
                            'account_name' => $candidate->name ?? null,
                            'live_meetings' => array_map(fn ($m) => [
                                'id'    => $m['id'] ?? null,
                                'topic' => $m['topic'] ?? null,
                            ], $live),
                        ],
                    ];
                    continue;
                }

                return $outcome($candidate, $onPick ? $onPick($candidate) : null, false);
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

        // No idle account worked. Fall back to the ones that are free for the window but
        // in a meeting now: the booking is still valid for its slot, and the start-link
        // endpoint warns the teacher if they try to open it too early.
        foreach ($inMeetingNow as $i => $row) {
            $candidate = $row['account'];

            try {
                $result = $onPick ? $onPick($candidate) : null;

                // The account actually used is no longer a "skipped" one.
                unset($inMeetingNow[$i]);

                return $outcome($candidate, $result, true);
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

        return $outcome(null, null, false);
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
     * Select a free account and create a new Zoom meeting for this event on it.
     *
     * Every call books a fresh meeting. Teachers click "create Zoom link" each time they
     * start a class, including on events that already carry a meeting from an earlier
     * session; handing that old meeting back pinned the class to whatever account it was
     * booked on weeks ago, with no check of what that account is doing now, so the host
     * was often refused ("You have a meeting that is currently in progress"). The old
     * meeting is deleted once the new one exists, unless it is running right now: then it
     * is returned unchanged, so a class in progress is never stranded.
     *
     * Bookings run one at a time. Selection reads the accounts' current meetings and the
     * booking writes to them, so two requests racing for the same slot would both see an
     * account as free and book it twice — and the second teacher could not start.
     *
     * @return array{account: ?MeetingAccount, meeting: ?array, busy: array, errors: array, total: int, reused?: bool, replaced_meeting?: array}
     */
    public function bookMeetingFor(Event $event): array
    {
        try {
            return $this->withBookingLock(fn () => $this->bookMeetingUnlocked($event));
        } catch (LockTimeoutException) {
            Log::warning('Zoom booking lock not acquired in time', ['event_id' => $event->id]);

            return [
                'account' => null,
                'meeting' => null,
                'busy'    => [],
                'errors'  => [[
                    'account_id'   => null,
                    'account_name' => null,
                    'error'        => 'Another meeting is being booked right now — please retry in a few seconds',
                ]],
                'total'   => max(1, MeetingAccount::where('is_active', true)->count()),
            ];
        }
    }

    private function bookMeetingUnlocked(Event $event): array
    {
        $previous = ($event->zoom_meeting_id && $event->meetingAccount)
            ? ['account' => $event->meetingAccount, 'meeting_id' => (string) $event->zoom_meeting_id]
            : null;

        // The class is in progress on its current meeting: leave it alone.
        if ($previous !== null && $this->isMeetingLive($previous['account'], $previous['meeting_id'])) {
            $running = $this->reuseExistingMeeting($event);

            if ($running !== null) {
                return $running;
            }
        }

        // The slot to protect starts now at the earliest. For a late start, or an old
        // event used again, the event's own time is in the past and a check against it
        // would never see a class that is running at this moment.
        $now         = \Carbon\Carbon::now('UTC');
        $scheduled   = $this->eventStartUtc($event);
        $windowStart = $scheduled->lt($now) ? $now : $scheduled;

        $outcome = $this->pickAccount(
            $windowStart,
            $this->durationFor($event),
            fn (MeetingAccount $account) => $this->createMeeting($account, $event, $windowStart),
            $previous['meeting_id'] ?? null
        );

        $outcome['meeting'] = $outcome['result'];
        unset($outcome['result']);

        if ($previous !== null && $outcome['account'] !== null) {
            $outcome['replaced_meeting'] = $this->releaseReplacedMeeting($previous['account'], $previous['meeting_id'], $event);
        }

        return $outcome;
    }

    /** Is this Zoom meeting running right now? A failed check counts as "no". */
    private function isMeetingLive(MeetingAccount $account, string $meetingId): bool
    {
        try {
            foreach ($this->currentLiveMeetings($account) as $live) {
                if ((string) ($live['id'] ?? '') === $meetingId) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Could not check whether the event\'s current Zoom meeting is live', [
                'account_id'      => $account->id,
                'zoom_meeting_id' => $meetingId,
                'error'           => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Delete the meeting an event has just been moved off, so it stops holding a slot.
     *
     * Never fails the booking: the new meeting already exists and is what the event now
     * points at. A meeting that is live is kept, since someone may be in it.
     *
     * @return array{account_id: int, zoom_meeting_id: string, deleted: bool, reason: ?string}
     */
    private function releaseReplacedMeeting(MeetingAccount $account, string $meetingId, Event $event): array
    {
        $result = ['account_id' => $account->id, 'zoom_meeting_id' => $meetingId, 'deleted' => false, 'reason' => null];

        try {
            if ($this->isMeetingLive($account, $meetingId)) {
                $result['reason'] = 'live';

                return $result;
            }

            $this->deleteMeeting($account, $meetingId);
            $result['deleted'] = true;
        } catch (\Throwable $e) {
            $result['reason'] = $e->getMessage();

            Log::warning('Could not delete the Zoom meeting an event was moved off', [
                'event_id'        => $event->id,
                'account_id'      => $account->id,
                'zoom_meeting_id' => $meetingId,
                'error'           => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Hand back the event's current Zoom meeting as it is: used only while that meeting is running.
     * Null when Zoom reports it as gone (404), in which case a new one is booked.
     *
     * Null only when Zoom reports the meeting as gone (404). Any other failure means
     * "we do not know", and booking a replacement then could duplicate a meeting that is
     * still there, so it is reported as an unreachable account for the caller to retry.
     */
    private function reuseExistingMeeting(Event $event): ?array
    {
        $account = $event->meetingAccount;

        try {
            $meeting = $this->getMeeting($account, (string) $event->zoom_meeting_id);
        } catch (\Throwable $e) {
            if ($e->getCode() === 404) {
                Log::info('Event\'s Zoom meeting no longer exists — booking a new one', [
                    'event_id'        => $event->id,
                    'zoom_meeting_id' => $event->zoom_meeting_id,
                ]);

                return null;
            }

            Log::warning('Could not check the event\'s existing Zoom meeting', [
                'event_id'        => $event->id,
                'zoom_meeting_id' => $event->zoom_meeting_id,
                'error'           => $e->getMessage(),
            ]);

            return [
                'account' => null,
                'meeting' => null,
                'busy'    => [],
                'errors'  => [[
                    'account_id'   => $account->id,
                    'account_name' => $account->name ?? null,
                    'error'        => $e->getMessage(),
                ]],
                'total'   => 1,
            ];
        }

        return [
            'account' => $account,
            'meeting' => [
                'meeting_id' => (string) $event->zoom_meeting_id,
                'join_url'   => $meeting['join_url'] ?? $event->event_meet_link,
                'start_url'  => $meeting['start_url'] ?? $event->event_start_link,
            ],
            'busy'    => [],
            'errors'  => [],
            'total'   => 1,
            'reused'  => true,
        ];
    }

    /**
     * Run the callback while holding the global booking lock.
     *
     * A cache store that cannot lock (or a broken one) must not stop teachers booking,
     * so those cases run unlocked — the same behaviour as before the lock existed.
     *
     * @throws LockTimeoutException when another booking holds the lock for too long
     */
    private function withBookingLock(callable $callback): array
    {
        try {
            $lock = Cache::lock('zoom:booking', self::BOOKING_LOCK_SECONDS);
        } catch (\Throwable $e) {
            Log::warning('Zoom booking lock unavailable — booking without it', ['error' => $e->getMessage()]);

            return $callback();
        }

        return $lock->block(self::BOOKING_LOCK_WAIT_SECONDS, $callback);
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
        $cacheKey = $account->id . ':' . $type;

        if (isset($this->listCache[$cacheKey])) {
            return $this->listCache[$cacheKey];
        }

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

        return $this->listCache[$cacheKey] = $meetings;
    }
}
