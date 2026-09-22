<?php

return [
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY', ''),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
    ],

    'zoom' => [
        /*
         * Changeover minutes required after a meeting ends before the same account
         * may host another. Applied only after an existing meeting, so an event may
         * end exactly when the next one begins.
         *
         * Note that any non-zero value prevents back-to-back bookings on one account:
         * with 20, a 10:00-11:00 meeting blocks an 11:00 start. Set this to 0 (or a
         * few minutes) if lessons are scheduled on the hour.
         */
        'buffer_minutes' => (int) env('ZOOM_MEETING_BUFFER_MINUTES', 20),
    ],
];
