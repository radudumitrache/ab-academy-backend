<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_completion_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->unique();
            $table->text('full_text'); // blanks represented as ___
            $table->json('correct_answers'); // array of correct strings per blank in order
            $table->timestamps();

            $table->foreign('question_id')->references('question_id')->on('questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_completion_questions');
    }
};
