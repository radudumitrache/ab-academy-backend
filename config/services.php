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

        /*
         * The longest a meeting may be, in minutes. Default 240 (4 hours).
         *
         * This is enforced in three places:
         *
         *   1. Validation — an event cannot be created or updated with a longer
         *      duration, so the platform can no longer produce an over-long meeting.
         *   2. Creating/updating a Zoom meeting — the duration sent to Zoom is clamped,
         *      which covers events stored before this limit existed.
         *   3. Conflict detection — an existing meeting counts as occupying its account
         *      for at most this long.
         *
         * (3) is what makes this matter. Zoom stores whatever duration it is given, and
         * these accounts hold meetings created with placeholder lengths of 9999, 99999
         * and 999999 minutes — 7, 69 and 694 days. Taken literally, one stale meeting
         * blocked its account into 2028, which is why every account reported as busy.
         *
         * Meetings at or under the cap are unaffected.
         */
        'max_meeting_minutes' => (int) env('ZOOM_MAX_MEETING_MINUTES', 240),

        /*
         * How long, from now, a live meeting with no start_time/duration (an instant
         * meeting or Personal Meeting Room) is assumed to keep its account busy.
         *
         * Zoom reports no end for these, so this only guards events that start almost
         * immediately. Events further out are not blocked by them.
         */
        'live_unknown_window_minutes' => (int) env('ZOOM_LIVE_UNKNOWN_WINDOW_MINUTES', 30),

        /*
         * How long after its scheduled start a meeting that is not live is still
         * assumed to be about to begin (a host running late).
         *
         * Zoom's scheduled list keeps meetings from the past month and does not say
         * whether they ran. Once a meeting is this far past its start and not live, it
         * has ended or never started, so it stops blocking its account.
         */
        'no_show_grace_minutes' => (int) env('ZOOM_NO_SHOW_GRACE_MINUTES', 15),
    ],
];
