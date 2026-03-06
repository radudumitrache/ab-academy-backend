<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('groups', 'schedule_days')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->json('schedule_days')->nullable()->after('description');
            });
        }

        // Migrate existing single schedule into the new array format
        DB::table('groups')->whereNotNull('schedule_day')->orderBy('group_id')->each(function ($group) {
            $entry = [
                'day'  => $group->schedule_day,
                'time' => $group->schedule_time ?? null,
            ];
            DB::table('groups')
                ->where('group_id', $group->group_id)
                ->update(['schedule_days' => json_encode([$entry])]);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['schedule_day', 'schedule_time']);
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('schedule_day')->nullable()->after('description');
            $table->time('schedule_time')->nullable()->after('schedule_day');
        });

        // Restore first entry back into the old columns
        DB::table('groups')->whereNotNull('schedule_days')->orderBy('group_id')->each(function ($group) {
            $entries = json_decode($group->schedule_days, true);
            if (!empty($entries)) {
                DB::table('groups')
                    ->where('group_id', $group->group_id)
                    ->update([
                        'schedule_day'  => $entries[0]['day']  ?? null,
                        'schedule_time' => $entries[0]['time'] ?? null,
                    ]);
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('schedule_days');
        });
    }
};
