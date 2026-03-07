<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_multiple_choice_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->json('variants');
            $table->integer('correct_variant');
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_gap_fill_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->boolean('with_variants')->default(false);
            $table->json('variants')->nullable();
            $table->json('correct_answers');
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_rephrase_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_word_formation_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->string('base_word');
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_replace_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('original_text');
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_correct_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('incorrect_text');
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_word_derivation_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->string('root_word');
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_text_completion_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('full_text');
            $table->json('correct_answers');
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_correlation_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->json('column_a');
            $table->json('column_b');
            $table->json('correct_pairs');
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });

        Schema::create('test_reading_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')->references('test_question_id')->on('test_questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_multiple_choice_questions');
        Schema::dropIfExists('test_gap_fill_questions');
        Schema::dropIfExists('test_rephrase_questions');
        Schema::dropIfExists('test_word_formation_questions');
        Schema::dropIfExists('test_replace_questions');
        Schema::dropIfExists('test_correct_questions');
        Schema::dropIfExists('test_word_derivation_questions');
        Schema::dropIfExists('test_text_completion_questions');
        Schema::dropIfExists('test_correlation_questions');
        Schema::dropIfExists('test_reading_questions');
    }
};
