<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->enum('section_type', ['GrammarAndVocabulary', 'Writing', 'Reading', 'Listening']);
            $table->string('title')->nullable();
            $table->json('instruction_files')->nullable();

            // Reading-specific
            $table->text('passage')->nullable();

            // Listening-specific
            $table->string('audio_url')->nullable();
            $table->unsignedBigInteger('audio_material_id')->nullable();
            $table->text('transcript')->nullable();

            $table->unsignedInteger('order')->nullable();
            $table->timestamps();

            $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
            $table->foreign('audio_material_id')->references('material_id')->on('materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_sections');
    }
};
