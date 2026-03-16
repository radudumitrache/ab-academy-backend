<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: drop the foreign keys that reference the unique index, then drop the unique index
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['student_id']);
            $table->dropUnique(['group_id', 'student_id', 'session_date', 'session_time']);
        });

        // Step 2: alter columns and add new column + FK
        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->change();
            $table->date('session_date')->nullable()->change();
            $table->time('session_time')->nullable()->change();

            $table->unsignedBigInteger('event_id')->nullable()->after('group_id');
        });

        // Step 3: re-add foreign keys and add new constraints
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
