<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['test_id', 'student_id']);

            $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('test_question_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('related_question');
            $table->unsignedBigInteger('related_student');
            $table->text('answer');
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('test_submissions')->cascadeOnDelete();
            $table->foreign('related_question')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
            $table->foreign('related_student')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_question_responses');
        Schema::dropIfExists('test_submissions');
    }
};
