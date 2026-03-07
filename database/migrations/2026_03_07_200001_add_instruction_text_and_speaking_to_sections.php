<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add instruction_text to homework_sections and extend section_type enum
        Schema::table('homework_sections', function (Blueprint $table) {
            $table->text('instruction_text')->nullable()->after('title');
        });

        // MySQL requires re-specifying the full enum to add a value
        \DB::statement("ALTER TABLE homework_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking') NOT NULL");

        // Add instruction_text to test_sections and extend section_type enum
        Schema::table('test_sections', function (Blueprint $table) {
            $table->text('instruction_text')->nullable()->after('title');
        });

        \DB::statement("ALTER TABLE test_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening','Speaking') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('homework_sections', function (Blueprint $table) {
            $table->dropColumn('instruction_text');
        });

        \DB::statement("ALTER TABLE homework_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening') NOT NULL");

        Schema::table('test_sections', function (Blueprint $table) {
            $table->dropColumn('instruction_text');
        });

        \DB::statement("ALTER TABLE test_sections MODIFY section_type ENUM('GrammarAndVocabulary','Writing','Reading','Listening') NOT NULL");
    }
};
