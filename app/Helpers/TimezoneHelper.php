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
}
