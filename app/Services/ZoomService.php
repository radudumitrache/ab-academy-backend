<?php

namespace App\Services;

use App\Models\Event;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    /**
     * Obtain a short-lived bearer token via Server-to-Server OAuth.
     */
    public function getAccessToken(MeetingAccount $account): string
    {
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

        return $response->json('access_token');
    }

    /**
     * Create a scheduled Zoom meeting for the given event.
     * Returns ['join_url' => ..., 'start_url' => ...].
     */
    public function createMeeting(MeetingAccount $account, Event $event): array
    {
        $token = $this->getAccessToken($account);

        // Build ISO-8601 start time: combine event_date + event_time
        $startTime = $event->event_date->format('Y-m-d') . 'T' . $event->event_time . ':00';

        $response = Http::withToken($token)
            ->post('https://api.zoom.us/v2/users/me/meetings', [
                'topic'      => $event->title,
                'type'       => 2, // scheduled
                'start_time' => $startTime,
                'timezone'   => 'UTC', // start_time is always stored and sent as UTC
                'duration'   => $event->event_duration,
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
            'join_url'  => $response->json('join_url'),
            'start_url' => $response->json('start_url'),
        ];
    }

    /**
     * Return the first overlapping Zoom meeting for this account, or null if free.
     * Overlap window: [eventStart, eventStart + durationMinutes).
     * Also returns a live meeting immediately, since a host cannot start a second meeting
     * while one is already in progress.
     */
    public function findOverlappingMeeting(MeetingAccount $account, \Carbon\Carbon $eventStart, int $durationMinutes): ?array
    {
        // A live (in-progress) meeting blocks the account regardless of scheduled times.
        $live = $this->listLiveMeetings($account);
        if (!empty($live)) {
            Log::debug('Zoom live meeting in progress', [
                'account_id'   => $account->id,
                'account_name' => $account->name ?? null,
                'live_topic'   => $live[0]['topic'] ?? null,
            ]);
            return $live[0];
        }

        $bufferMinutes = 20;

        $meetings = $this->listMeetings($account);
        $eventEnd = $eventStart->copy()->addMinutes($durationMinutes);

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
            $mEnd            = $mStart->copy()->addMinutes((int) $meeting['duration']);
            $mEndWithBuffer  = $mEnd->copy()->addMinutes($bufferMinutes);

            // Conflict if the existing meeting (plus buffer) overlaps the new event window.
            if ($mStart->lt($eventEnd->copy()->addMinutes($bufferMinutes)) && $mEndWithBuffer->gt($eventStart)) {
                Log::debug('Zoom overlap found', [
                    'account_id'             => $account->id,
                    'conflicting_topic'      => $meeting['topic'] ?? null,
                    'conflicting_start'      => $mStart->toIso8601String(),
                    'conflicting_end'        => $mEnd->toIso8601String(),
                    'conflicting_end_buffer' => $mEndWithBuffer->toIso8601String(),
                ]);
                return $meeting;
            }
        }

        return null;
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
        $token = $this->getAccessToken($account);

        $response = Http::withToken($token)
            ->get('https://api.zoom.us/v2/users/me/meetings', [
                'type'      => 'scheduled',
                'page_size' => 300,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom meeting list failed: ' . $response->body()
            );
        }

        return $response->json('meetings') ?? [];
    }

    /**
     * Fetch all currently live (in-progress) meetings for the account from the Zoom API.
     */
    public function listLiveMeetings(MeetingAccount $account): array
    {
        $token = $this->getAccessToken($account);

        $response = Http::withToken($token)
            ->get('https://api.zoom.us/v2/users/me/meetings', [
                'type'      => 'live',
                'page_size' => 300,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Zoom live meeting list failed: ' . $response->body()
            );
        }

        return $response->json('meetings') ?? [];
    }
}
