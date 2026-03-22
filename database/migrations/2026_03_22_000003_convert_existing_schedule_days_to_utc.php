<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Convert all existing schedule_days times from Europe/Bucharest (the implicit
     * timezone all data was entered in) to UTC.
     *
     * Uses the same fixed anchor date (2026-01-05, a Monday in standard/winter time)
     * as TimezoneHelper::scheduleTimeToUtc() to ensure consistency with the application.
     */
    public function up(): void
    {
        $groups = DB::table('groups')
            ->whereNotNull('schedule_days')
            ->where('schedule_days', '!=', '[]')
            ->get(['group_id', 'schedule_days']);

        foreach ($groups as $group) {
            $days = json_decode($group->schedule_days, true);

            if (!is_array($days)) {
                continue;
            }

            $converted = array_map(function ($slot) {
                if (empty($slot['time'])) {
                    return $slot;
                }

                $timeStr = substr($slot['time'], 0, 5);

                $utc = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    '2026-01-05 ' . $timeStr,
                    'Europe/Bucharest'
                )->setTimezone('UTC');

                $slot['time'] = $utc->format('H:i');

                return $slot;
            }, $days);

            DB::table('groups')
                ->where('group_id', $group->group_id)
                ->update(['schedule_days' => json_encode($converted)]);
        }
    }

    /**
     * Reverse: convert UTC back to Europe/Bucharest.
     */
    public function down(): void
    {
        $groups = DB::table('groups')
            ->whereNotNull('schedule_days')
            ->where('schedule_days', '!=', '[]')
            ->get(['group_id', 'schedule_days']);

        foreach ($groups as $group) {
            $days = json_decode($group->schedule_days, true);

            if (!is_array($days)) {
                continue;
            }

            $converted = array_map(function ($slot) {
                if (empty($slot['time'])) {
                    return $slot;
                }

                $timeStr = substr($slot['time'], 0, 5);

                $local = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    '2026-01-05 ' . $timeStr,
                    'UTC'
                )->setTimezone('Europe/Bucharest');

                $slot['time'] = $local->format('H:i');

                return $slot;
            }, $days);

            DB::table('groups')
                ->where('group_id', $group->group_id)
                ->update(['schedule_days' => json_encode($converted)]);
        }
    }
};
