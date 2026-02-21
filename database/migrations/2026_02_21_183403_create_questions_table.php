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
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->text('question_text');
            $table->unsignedBigInteger('homework_id');
            $table->string('question_type')->default('basic'); // To identify the type of question (basic, multiple_choice, etc.)
            $table->timestamps();
            
            $table->foreign('homework_id')->references('id')->on('homework')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
