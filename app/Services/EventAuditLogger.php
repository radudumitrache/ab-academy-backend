<?php

namespace App\Services;

use App\Models\DatabaseLog;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Records attempts to create an event or a Zoom meeting in the database log, together
 * with the circumstances and the API response the caller received.
 *
 * Built to trace intermittent Zoom booking failures: every attempt is stored, successful
 * or not, with what was asked for, what the pool looked like, and what came back.
 *
 * Writing the log must never break the request it describes, so everything here is
 * wrapped and falls back to the application log.
 */
class EventAuditLogger
{
    /** Values that must not be written to the log, wherever they appear in a payload. */
    private const REDACTED_KEYS = [
        'start_link', 'event_start_link', 'start_url', 'password', 'client_secret', 'access_token',
    ];

    /**
     * What the event pointed at before a booking attempt, for the "was this a retry?" question.
     */
    public static function meetingSnapshot(Event $event): array
    {
        return [
            'meeting_account_id' => $event->meeting_account_id,
            'zoom_meeting_id'    => $event->zoom_meeting_id,
        ];
    }

    /**
     * Log one attempt to create a Zoom meeting for an event.
     *
     * @param  string      $source    'teacher' or 'admin'
     * @param  array|null  $outcome   ZoomService::bookMeetingFor() result; null if it threw
     * @param  array       $before    meetingSnapshot() taken before booking
     * @param  mixed       $body      the JSON body the API returned
     */
    public static function zoomAttempt(
        string $source,
        Event $event,
        ?array $outcome,
        array $before,
        int $status,
        mixed $body,
        ?\Throwable $error = null
    ): void {
        try {
            $result = match (true) {
                $error !== null                 => 'exception',
                $status === 403                 => 'forbidden',
                !empty($outcome['reused'])      => 'reused_existing',
                !empty($outcome['account'])     => 'created',
                !empty($outcome['errors'])      => 'accounts_unreachable',
                default                         => 'no_free_account',
            };

            $ok = in_array($result, ['created', 'reused_existing'], true);

            $now    = Carbon::now('UTC');
            $window = self::window($event, $now);

            DatabaseLog::logAction(
                $ok ? 'zoom_create_ok' : 'zoom_create_failed',
                Event::class,
                $event->id,
                sprintf(
                    "Zoom meeting attempt for event #%d '%s' by %s: %s (HTTP %d)%s",
                    $event->id,
                    $event->title,
                    $source,
                    $result,
                    $status,
                    $error ? ' — ' . $error->getMessage() : ''
                ),
                [
                    'source'      => $source,
                    'result'      => $result,
                    'http_status' => $status,
                    'actor'       => self::actor(),
                    'event'       => [
                        'id'                  => $event->id,
                        'title'               => $event->title,
                        'type'                => $event->type,
                        'event_date_utc'      => $event->event_date?->format('Y-m-d'),
                        'event_time_utc'      => is_string($event->event_time) ? substr($event->event_time, 0, 5) : null,
                        'duration_min'        => $event->event_duration,
                        'organizer_id'        => $event->event_organizer,
                        'guest_groups'        => $event->guest_groups,
                        'guest_count'         => count($event->guests ?? []),
                    ],
                    'before'      => $before,
                    'window'      => $window,
                    'pool'        => [
                        'active_accounts' => $outcome['total'] ?? null,
                        'busy'            => $outcome['busy'] ?? [],
                        'errors'          => $outcome['errors'] ?? [],
                    ],
                    'chosen'      => [
                        'account_id'      => $outcome['account']->id ?? null,
                        'account_name'    => $outcome['account']->name ?? null,
                        'zoom_meeting_id' => $outcome['meeting']['meeting_id'] ?? null,
                    ],
                    'api_response' => self::redact($body),
                    'exception'    => $error ? ['class' => get_class($error), 'message' => $error->getMessage()] : null,
                    'request'      => self::requestContext(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Could not write the Zoom attempt to the database log', [
                'event_id' => $event->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log one attempt to create an event (accepted or rejected).
     *
     * @param  array  $payload  the request input as sent (local time, before UTC conversion)
     */
    public static function eventAttempt(
        string $source,
        Request $request,
        ?Event $event,
        int $status,
        mixed $body,
        ?\Throwable $error = null
    ): void {
        try {
            $ok = $event !== null && $status < 400;

            DatabaseLog::logAction(
                $ok ? 'event_create_ok' : 'event_create_failed',
                Event::class,
                $event?->id,
                sprintf(
                    "Event create attempt by %s: %s (HTTP %d)%s",
                    $source,
                    $ok ? "created #{$event->id} '{$event->title}'" : 'rejected',
                    $status,
                    $error ? ' — ' . $error->getMessage() : ''
                ),
                [
                    'source'       => $source,
                    'http_status'  => $status,
                    'actor'        => self::actor(),
                    // Exactly what the client sent, before the conversion to UTC.
                    'request_input' => self::redact([
                        'title'          => $request->input('title'),
                        'type'           => $request->input('type'),
                        'event_date'     => $request->input('event_date'),
                        'event_time'     => $request->input('event_time'),
                        'event_duration' => $request->input('event_duration'),
                        'event_organizer' => $request->input('event_organizer'),
                        'guest_groups'   => $request->input('guest_groups'),
                        'guest_count'    => is_array($request->input('guests')) ? count($request->input('guests')) : 0,
                    ]),
                    'stored'       => $event ? [
                        'event_id'       => $event->id,
                        'event_date_utc' => $event->event_date?->format('Y-m-d'),
                        'event_time_utc' => is_string($event->event_time) ? substr($event->event_time, 0, 5) : null,
                    ] : null,
                    'api_response' => self::redact($body),
                    'exception'    => $error ? ['class' => get_class($error), 'message' => $error->getMessage()] : null,
                    'request'      => self::requestContext(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Could not write the event attempt to the database log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log one request for a host start link, with everything needed to explain a later
     * "you have a meeting that is currently in progress" from Zoom.
     *
     * That message appears in the teacher's Zoom app, after they click the link, so the
     * server never sees it. What can be recorded is the account's state at the moment the
     * link was handed out: which meetings were live on it, which one would block this
     * start, how it got there, and which of our own events it belongs to.
     *
     * @param  array|null  $zoomMeeting   getMeeting() result, null if it could not be fetched
     * @param  array|null  $liveMeetings  the account's live meetings, null if that check failed
     * @param  array|null  $blocking      a live meeting other than this event's own
     */
    public static function startLinkAttempt(
        string $source,
        Event $event,
        ?array $zoomMeeting,
        ?array $liveMeetings,
        ?array $blocking,
        int $status,
        mixed $body,
        ?\Throwable $fetchError = null,
        ?\Throwable $liveCheckError = null
    ): void {
        try {
            $result = match (true) {
                $status === 403                => 'forbidden',
                $status === 422                => 'no_meeting_to_start',
                $fetchError !== null           => 'zoom_fetch_failed',
                $blocking !== null             => 'blocked_by_live_meeting',
                $liveCheckError !== null       => 'live_check_failed',
                default                        => 'ok',
            };

            $now        = Carbon::now('UTC');
            $ownId      = (string) $event->zoom_meeting_id;
            $liveIds    = array_map(fn ($m) => (string) ($m['id'] ?? ''), $liveMeetings ?? []);
            $account    = $event->meetingAccount;

            DatabaseLog::logAction(
                $result === 'ok' ? 'zoom_start_ok' : 'zoom_start_failed',
                Event::class,
                $event->id,
                sprintf(
                    "Zoom start link for event #%d '%s' by %s: %s (HTTP %d)%s",
                    $event->id,
                    $event->title,
                    $source,
                    $result,
                    $status,
                    $blocking ? sprintf(" — blocked by live meeting %s '%s'", $blocking['id'] ?? '?', $blocking['topic'] ?? '') : ''
                ),
                [
                    'source'      => $source,
                    'result'      => $result,
                    'http_status' => $status,
                    'actor'       => self::actor(),
                    'event'       => [
                        'id'              => $event->id,
                        'title'           => $event->title,
                        'organizer_id'    => $event->event_organizer,
                        'zoom_meeting_id' => $event->zoom_meeting_id,
                        'event_date_utc'  => $event->event_date?->format('Y-m-d'),
                        'event_time_utc'  => is_string($event->event_time) ? substr($event->event_time, 0, 5) : null,
                        'duration_min'    => $event->event_duration,
                    ],
                    'timing'      => self::window($event, $now),
                    'account'     => $account ? ['id' => $account->id, 'name' => $account->name] : null,
                    // What Zoom says about this event's own meeting.
                    'own_meeting' => [
                        'fetched'       => $zoomMeeting !== null,
                        'zoom'          => $zoomMeeting ? self::pickMeetingFields($zoomMeeting) : null,
                        'is_live_now'   => $liveMeetings === null ? null : in_array($ownId, $liveIds, true),
                    ],
                    // Everything live on the account when the link was issued.
                    'live_meetings' => $liveMeetings === null ? null : array_map(
                        fn ($m) => self::describeLiveMeeting($m, $now),
                        $liveMeetings
                    ),
                    'blocking_meeting' => $blocking ? self::describeLiveMeeting($blocking, $now) : null,
                    // The same account's other events around this time, from our own database.
                    'account_events_nearby' => self::eventsNearby($event),
                    'api_response' => self::redact($body),
                    'fetch_error'  => $fetchError ? ['class' => get_class($fetchError), 'code' => $fetchError->getCode(), 'message' => $fetchError->getMessage()] : null,
                    'live_check_error' => $liveCheckError ? ['class' => get_class($liveCheckError), 'message' => $liveCheckError->getMessage()] : null,
                    'request'      => self::requestContext(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Could not write the Zoom start-link attempt to the database log', [
                'event_id' => $event->id ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /** Safe, useful fields of a Zoom meeting. Never the start/join URLs or passcodes. */
    private static function pickMeetingFields(array $meeting): array
    {
        return array_intersect_key($meeting, array_flip([
            'id', 'uuid', 'topic', 'type', 'status', 'start_time', 'duration', 'timezone',
            'host_id', 'host_email', 'created_at',
        ]));
    }

    /**
     * A live meeting plus the facts that explain why it holds the account, and which of
     * our own events (if any) it is.
     */
    private static function describeLiveMeeting(array $live, Carbon $now): array
    {
        $info = self::pickMeetingFields($live);

        $start    = null;
        $duration = (int) ($live['duration'] ?? 0);

        try {
            $start = !empty($live['start_time']) ? Carbon::parse($live['start_time'])->utc() : null;
        } catch (\Throwable) {
            $start = null;
        }

        if ($start === null || $duration <= 0) {
            // Personal Meeting Rooms and instant meetings report no schedule.
            $info['kind'] = 'no_schedule (personal room or instant meeting)';
        } else {
            $scheduledEnd = $start->copy()->addMinutes($duration);
            $info['kind']                = $scheduledEnd->lt($now) ? 'overran_its_scheduled_end' : 'within_scheduled_window';
            $info['started_minutes_ago'] = (int) $start->diffInMinutes($now, false);
            $info['scheduled_end_utc']   = $scheduledEnd->toIso8601String();
            $info['overran_by_minutes']  = max(0, (int) $scheduledEnd->diffInMinutes($now, false));
        }

        // Is it a meeting this platform booked? That says whose class is still running.
        $mine = isset($live['id'])
            ? Event::where('zoom_meeting_id', (string) $live['id'])->first(['id', 'title', 'event_organizer', 'event_date', 'event_time', 'event_duration'])
            : null;

        $info['platform_event'] = $mine ? [
            'event_id'     => $mine->id,
            'title'        => $mine->title,
            'organizer_id' => $mine->event_organizer,
            'date_utc'     => $mine->event_date?->format('Y-m-d'),
            'time_utc'     => is_string($mine->event_time) ? substr($mine->event_time, 0, 5) : null,
            'duration_min' => $mine->event_duration,
        ] : null; // null: created outside the platform (Zoom app, PMR, another tool)

        return $info;
    }

    /** Other events booked on the same Zoom account within a few hours of this one. */
    private static function eventsNearby(Event $event): array
    {
        if (!$event->meeting_account_id || !$event->event_date) {
            return [];
        }

        try {
            $start = app(ZoomService::class)->eventStartUtc($event);

            return Event::where('meeting_account_id', $event->meeting_account_id)
                ->where('id', '!=', $event->id)
                ->whereBetween('event_date', [
                    $start->copy()->subDay()->format('Y-m-d'),
                    $start->copy()->addDay()->format('Y-m-d'),
                ])
                ->get(['id', 'title', 'event_organizer', 'event_date', 'event_time', 'event_duration', 'zoom_meeting_id'])
                ->map(function (Event $e) use ($start) {
                    $s = app(ZoomService::class)->eventStartUtc($e);

                    return [
                        'event_id'           => $e->id,
                        'title'              => $e->title,
                        'organizer_id'       => $e->event_organizer,
                        'start_utc'          => $s->toIso8601String(),
                        'end_utc'            => $s->copy()->addMinutes((int) $e->event_duration)->toIso8601String(),
                        'minutes_from_this_start' => (int) round($start->diffInSeconds($s, false) / 60),
                        'zoom_meeting_id'    => $e->zoom_meeting_id,
                    ];
                })
                ->filter(fn ($row) => abs($row['minutes_from_this_start']) <= 240)
                ->sortBy('minutes_from_this_start')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** The time slot that was checked, and how far away it is. */
    private static function window(Event $event, Carbon $now): ?array
    {
        try {
            $start    = app(ZoomService::class)->eventStartUtc($event);
            $duration = (int) $event->event_duration;

            return [
                'requested_at_utc'   => $now->toIso8601String(),
                'start_utc'          => $start->toIso8601String(),
                'end_utc'            => $start->copy()->addMinutes($duration)->toIso8601String(),
                // Negative when the slot has already started.
                'minutes_until_start' => (int) round($now->diffInSeconds($start, false) / 60),
                'buffer_minutes'     => (int) config('services.zoom.buffer_minutes', 20),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private static function actor(): array
    {
        $user = auth('api')->user();

        return [
            'id'       => $user?->id,
            'username' => $user?->username,
            'role'     => $user?->role,
            'timezone' => $user?->effective_timezone,
        ];
    }

    private static function requestContext(): array
    {
        $request = request();

        return [
            'method'     => $request->method(),
            'path'       => $request->path(),
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ];
    }

    /** Replace sensitive values, at any depth, with a marker. */
    private static function redact(mixed $value): mixed
    {
        if (is_string($value)) {
            // Join and start URLs carry credentials in the query string.
            return preg_replace('/\b(zak|pwd|password|token)=[^&\s"\']+/i', '$1=[redacted]', $value);
        }

        if (!is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = is_string($key) && in_array($key, self::REDACTED_KEYS, true)
                ? '[redacted]'
                : self::redact($item);
        }

        return $out;
    }
}
