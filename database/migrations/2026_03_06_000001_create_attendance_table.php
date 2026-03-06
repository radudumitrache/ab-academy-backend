<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('student_id');
            $table->date('session_date');
            $table->time('session_time');
            $table->enum('status', ['present', 'absent', 'motivated_absent']);
            $table->timestamps();

            $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');

            // One record per student per session
            $table->unique(['group_id', 'student_id', 'session_date', 'session_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
