<?php

namespace App\Services;

use App\Models\Event;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Http;

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
     * Return true if this account already has a Zoom meeting that overlaps the
     * given UTC start time + duration window.
     */
    public function hasOverlappingMeeting(MeetingAccount $account, \Carbon\Carbon $eventStart, int $durationMinutes): bool
    {
        $meetings = $this->listMeetings($account);
        $eventEnd = $eventStart->copy()->addMinutes($durationMinutes);

        foreach ($meetings as $meeting) {
            if (empty($meeting['start_time']) || empty($meeting['duration'])) {
                continue;
            }
            $mStart = \Carbon\Carbon::parse($meeting['start_time'])->utc();
            $mEnd   = $mStart->copy()->addMinutes((int) $meeting['duration']);

            if ($mStart->lt($eventEnd) && $mEnd->gt($eventStart)) {
                return true;
            }
        }

        return false;
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
}
