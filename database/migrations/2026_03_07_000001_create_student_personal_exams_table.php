<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_personal_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('exam_type')->nullable();   // e.g. IELTS, Cambridge B2, TOEFL
            $table->date('date')->nullable();
            $table->string('score')->nullable();        // free text: "7.5", "B2", "87/100"
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_personal_exams');
    }
};
