<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend section_type enums to include 'Mixed'
        DB::statement("ALTER TABLE test_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking','Mixed') NOT NULL");
        DB::statement("ALTER TABLE homework_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking','Mixed') NOT NULL");

        // Create test_mixed_questions detail table
        Schema::create('test_mixed_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('test_question_id')
                ->references('test_question_id')
                ->on('test_questions')
                ->cascadeOnDelete();
        });

        // Create mixed_questions detail table (for homework)
        Schema::create('mixed_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->unique();
            $table->text('sample_answer')->nullable();
            $table->timestamps();

            $table->foreign('question_id')
                ->references('question_id')
                ->on('questions')
                ->cascadeOnDelete();
        });

        // Add file_path to test_question_responses (for student file submissions on tests)
        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::dropIfExists('mixed_questions');
        Schema::dropIfExists('test_mixed_questions');

        DB::statement("ALTER TABLE test_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking') NOT NULL");
        DB::statement("ALTER TABLE homework_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking') NOT NULL");
    }
};
