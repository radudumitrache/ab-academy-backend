<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('homework_id');
            $table->string('title')->nullable();
            $table->string('audio_url');
            $table->text('transcript')->nullable();
            $table->unsignedInteger('order')->nullable();
            $table->timestamps();

            $table->foreign('homework_id')->references('id')->on('homework')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_sections');
    }
};
