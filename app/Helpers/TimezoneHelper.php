<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimezoneHelper
{
    /**
     * Convert a local date + time to a UTC Carbon instance.
     *
     * @param  string  $date      Date string in Y-m-d format
     * @param  string  $time      Time string in H:i or H:i:s format
     * @param  string  $timezone  IANA timezone (e.g. "Europe/Bucharest")
     */
    public static function toUtc(string $date, string $time, string $timezone): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . substr($time, 0, 5), $timezone)
            ->setTimezone('UTC');
    }

    /**
     * Convert a UTC Carbon instance to a local date + time array.
     *
     * @param  Carbon  $utc       Carbon instance in UTC
     * @param  string  $timezone  IANA timezone (e.g. "Europe/Bucharest")
     * @return array{date: string, time: string}  ['date' => 'Y-m-d', 'time' => 'H:i']
     */
    public static function fromUtc(Carbon $utc, string $timezone): array
    {
        $local = $utc->copy()->setTimezone($timezone);

        return [
            'date' => $local->format('Y-m-d'),
            'time' => $local->format('H:i'),
        ];
    }

    /**
     * Convert a recurring weekly schedule time (HH:MM) from a local timezone to UTC.
     *
     * Uses a fixed anchor date in standard time (2026-01-05) to produce a stable
     * UTC offset that is not affected by the current date's DST state.
     *
     * @param  string  $time      Time string in H:i format (e.g. "18:00")
     * @param  string  $timezone  IANA timezone of the actor submitting the schedule
     * @return string  UTC time in H:i format
     */
    public static function scheduleTimeToUtc(string $time, string $timezone): string
    {
        $anchor = Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 ' . substr($time, 0, 5), $timezone)
            ->setTimezone('UTC');

        return $anchor->format('H:i');
    }

    /**
     * Convert a recurring weekly schedule time (HH:MM) from UTC to a local timezone.
     *
     * Uses the same fixed anchor date as scheduleTimeToUtc to ensure the round-trip
     * is consistent.
     *
     * @param  string  $utcTime   UTC time string in H:i format
     * @param  string  $timezone  IANA timezone of the requesting user
     * @return string  Local time in H:i format
     */
    public static function scheduleTimeFromUtc(string $utcTime, string $timezone): string
    {
        $anchor = Carbon::createFromFormat('Y-m-d H:i', '2026-01-05 ' . substr($utcTime, 0, 5), 'UTC')
            ->setTimezone($timezone);

        return $anchor->format('H:i');
    }
}
