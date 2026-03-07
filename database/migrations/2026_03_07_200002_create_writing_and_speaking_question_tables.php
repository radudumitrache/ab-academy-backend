<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Homework writing questions
        Schema::create('writing_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('question_id')->references('question_id')->on('questions')->cascadeOnDelete();
        });

        // Homework speaking questions (files attached at question level)
        Schema::create('speaking_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->unique();
            $table->json('instruction_files')->nullable();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('question_id')->references('question_id')->on('questions')->cascadeOnDelete();
        });

        // Test writing questions
        Schema::create('test_writing_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        // Test speaking questions
        Schema::create('test_speaking_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->json('instruction_files')->nullable();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_questions');
        Schema::dropIfExists('speaking_questions');
        Schema::dropIfExists('test_writing_questions');
        Schema::dropIfExists('test_speaking_questions');
    }
};
