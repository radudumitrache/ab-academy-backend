<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('achievement_key', 50); // e.g. "early_bird"
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['student_id', 'achievement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
    }
};
