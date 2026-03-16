<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Make group_id nullable — attendance rows may belong to an event instead
            $table->unsignedBigInteger('group_id')->nullable()->change();

            // Add event FK
            $table->unsignedBigInteger('event_id')->nullable()->after('group_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');

            // Also make session_date / session_time nullable (events don't need them)
            $table->date('session_date')->nullable()->change();
            $table->time('session_time')->nullable()->change();

            // Drop the old unique constraint and replace with a broader one
            $table->dropUnique(['group_id', 'student_id', 'session_date', 'session_time']);
        });

        // Add new unique constraints separately (MySQL requires this after dropUnique)
        Schema::table('attendance', function (Blueprint $table) {
            // One record per student per group session
            $table->unique(['group_id', 'student_id', 'session_date', 'session_time'], 'attendance_group_session_unique');
            // One record per student per event
            $table->unique(['event_id', 'student_id'], 'attendance_event_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropUnique('attendance_group_session_unique');
            $table->dropUnique('attendance_event_student_unique');
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
            $table->date('session_date')->nullable(false)->change();
            $table->time('session_time')->nullable(false)->change();
            $table->unique(['group_id', 'student_id', 'session_date', 'session_time']);
        });
    }
};
