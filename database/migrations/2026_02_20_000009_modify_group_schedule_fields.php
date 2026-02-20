<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table instead of altering it
        if (Schema::hasTable('groups')) {
            // Get existing data
            $groups = DB::table('groups')->get();
            
            // Add new columns to the groups table
            Schema::table('groups', function (Blueprint $table) {
                // Drop the normal_schedule column if it exists
                if (Schema::hasColumn('groups', 'normal_schedule')) {
                    // First create the new columns
                    $table->string('schedule_day')->nullable()->after('description');
                    $table->time('schedule_time')->nullable()->after('schedule_day');
                    
                    // We can't drop columns in SQLite, so we'll handle this differently
                    // by copying data in the next step
                }
            });
            
            // Copy data from normal_schedule to the new columns
            foreach ($groups as $group) {
                if (isset($group->normal_schedule)) {
                    try {
                        $date = new DateTime($group->normal_schedule);
                        $day = $date->format('l'); // Day name (Monday, Tuesday, etc.)
                        $time = $date->format('H:i:s'); // Time in 24-hour format
                        
                        DB::table('groups')
                            ->where('group_id', $group->group_id)
                            ->update([
                                'schedule_day' => $day,
                                'schedule_time' => $time,
                            ]);
                    } catch (Exception $e) {
                        // If date parsing fails, just leave the new fields null
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('groups')) {
            // We can't easily convert back to a single datetime field
            // So we'll just add the column back if it doesn't exist
            if (!Schema::hasColumn('groups', 'normal_schedule')) {
                Schema::table('groups', function (Blueprint $table) {
                    $table->datetime('normal_schedule')->nullable()->after('description');
                });
            }
            
            // We won't drop the new columns in down() to prevent data loss
        }
    }
};
