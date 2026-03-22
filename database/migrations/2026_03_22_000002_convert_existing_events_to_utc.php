<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        $events = DB::table('events')
            ->whereNotNull('event_date')
            ->whereNotNull('event_time')
            ->get(['id', 'event_date', 'event_time']);

        foreach ($events as $event) {
            // event_time may be stored as "HH:MM:SS" or "HH:MM"
            $timeStr = substr($event->event_time, 0, 5);

            // Parse as Europe/Bucharest (the implicit timezone all existing data was entered in)
            $local = Carbon::createFromFormat(
                'Y-m-d H:i',
                $event->event_date . ' ' . $timeStr,
                'Europe/Bucharest'
            );

            $utc = $local->copy()->setTimezone('UTC');

            DB::table('events')->where('id', $event->id)->update([
                'event_date' => $utc->format('Y-m-d'),
                'event_time' => $utc->format('H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        // Reverse: convert UTC back to Europe/Bucharest
        $events = DB::table('events')
            ->whereNotNull('event_date')
            ->whereNotNull('event_time')
            ->get(['id', 'event_date', 'event_time']);

        foreach ($events as $event) {
            $timeStr = substr($event->event_time, 0, 5);
            $utc = Carbon::createFromFormat('Y-m-d H:i', $event->event_date . ' ' . $timeStr, 'UTC');
            $local = $utc->copy()->setTimezone('Europe/Bucharest');

            DB::table('events')->where('id', $event->id)->update([
                'event_date' => $local->format('Y-m-d'),
                'event_time' => $local->format('H:i:s'),
            ]);
        }
    }
};
