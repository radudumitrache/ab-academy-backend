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
