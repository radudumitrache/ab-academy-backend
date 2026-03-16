<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::getDatabaseName();

        // Step 1: drop all foreign keys on the attendance table dynamically (avoids name guessing)
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'attendance'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$db]);

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE `attendance` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Step 2: drop the old unique index
        $indexes = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'attendance'
              AND NON_UNIQUE = 0
              AND INDEX_NAME != 'PRIMARY'
        ", [$db]);

        $dropped = [];
        foreach ($indexes as $idx) {
            if (!in_array($idx->INDEX_NAME, $dropped)) {
                DB::statement("ALTER TABLE `attendance` DROP INDEX `{$idx->INDEX_NAME}`");
                $dropped[] = $idx->INDEX_NAME;
            }
        }

        // Step 3: alter columns and add event_id
        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->change();
            $table->date('session_date')->nullable()->change();
            $table->time('session_time')->nullable()->change();
            $table->unsignedBigInteger('event_id')->nullable()->after('group_id');
        });

        // Step 4: re-add foreign keys and constraints
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->unique(['group_id', 'student_id', 'session_date', 'session_time'], 'attendance_group_session_unique');
            $table->unique(['event_id', 'student_id'], 'attendance_event_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['event_id']);
            $table->dropUnique('attendance_group_session_unique');
            $table->dropUnique('attendance_event_student_unique');
            $table->dropColumn('event_id');
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
            $table->date('session_date')->nullable(false)->change();
            $table->time('session_time')->nullable(false)->change();
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['group_id', 'student_id', 'session_date', 'session_time']);
        });
    }
};
