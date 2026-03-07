<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id('test_question_id');
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('test_section_id');
            $table->text('question_text');
            $table->string('question_type');
            $table->json('instruction_files')->nullable();
            $table->unsignedInteger('order')->nullable();
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
            $table->foreign('test_section_id')->references('id')->on('test_sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_questions');
    }
};
