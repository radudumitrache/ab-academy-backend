<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gap_fill_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->unique();
            $table->boolean('with_variants')->default(false);
            $table->json('variants')->nullable(); // shown options when with_variants = true
            $table->json('correct_answers');      // array of correct strings per blank
            $table->timestamps();

            $table->foreign('question_id')->references('question_id')->on('questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gap_fill_questions');
    }
};
