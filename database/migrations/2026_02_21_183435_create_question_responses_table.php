<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->unsignedBigInteger('related_question');
            $table->unsignedBigInteger('related_student');
            $table->text('answer');
            $table->timestamps();
            
            $table->foreign('related_question')->references('question_id')->on('questions')->onDelete('cascade');
            $table->foreign('related_student')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_responses');
    }
};
