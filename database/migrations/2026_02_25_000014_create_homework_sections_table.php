<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('homework_id');
            $table->enum('section_type', ['GrammarAndVocabulary', 'Writing', 'Reading', 'Listening']);
            $table->string('title')->nullable();
            $table->json('instruction_files')->nullable(); // array of URLs

            // Reading-specific
            $table->text('passage')->nullable();

            // Listening-specific
            $table->string('audio_url')->nullable();
            $table->text('transcript')->nullable();

            $table->unsignedInteger('order')->nullable();
            $table->timestamps();

            $table->foreign('homework_id')->references('id')->on('homework')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_sections');
    }
};
