<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Convert all existing attendance session_date + session_time from Europe/Bucharest
     * (the implicit timezone all data was entered in) to UTC.
     */
    public function up(): void
    {
        $records = DB::table('attendance')
            ->whereNotNull('session_date')
            ->whereNotNull('session_time')
            ->get(['id', 'session_date', 'session_time']);

        foreach ($records as $record) {
            $timeStr = substr($record->session_time, 0, 5);

            $utc = Carbon::createFromFormat(
                'Y-m-d H:i',
                $record->session_date . ' ' . $timeStr,
                'Europe/Bucharest'
            )->setTimezone('UTC');

            DB::table('attendance')->where('id', $record->id)->update([
                'session_date' => $utc->format('Y-m-d'),
                'session_time' => $utc->format('H:i:s'),
            ]);
        }
    }

    /**
     * Reverse: convert UTC back to Europe/Bucharest.
     */
    public function down(): void
    {
        $records = DB::table('attendance')
            ->whereNotNull('session_date')
            ->whereNotNull('session_time')
            ->get(['id', 'session_date', 'session_time']);

        foreach ($records as $record) {
            $timeStr = substr($record->session_time, 0, 5);

            $local = Carbon::createFromFormat(
                'Y-m-d H:i',
                $record->session_date . ' ' . $timeStr,
                'UTC'
            )->setTimezone('Europe/Bucharest');

            DB::table('attendance')->where('id', $record->id)->update([
                'session_date' => $local->format('Y-m-d'),
                'session_time' => $local->format('H:i:s'),
            ]);
        }
    }
};
