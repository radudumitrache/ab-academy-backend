<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('homework_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('status', ['in_progress', 'submitted'])->default('in_progress');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['homework_id', 'student_id']);

            $table->foreign('homework_id')->references('id')->on('homework')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
    }
};
