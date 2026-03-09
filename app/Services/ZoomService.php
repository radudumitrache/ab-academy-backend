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
     * Returns the join_url.
     */
    public function createMeeting(MeetingAccount $account, Event $event): string
    {
        $token = $this->getAccessToken($account);

        // Build ISO-8601 start time: combine event_date + event_time
        $startTime = $event->event_date->format('Y-m-d') . 'T' . $event->event_time . ':00';

        $response = Http::withToken($token)
            ->post('https://api.zoom.us/v2/users/me/meetings', [
                'topic'      => $event->title,
                'type'       => 2, // scheduled
                'start_time' => $startTime,
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

        return $response->json('join_url');
    }
}
