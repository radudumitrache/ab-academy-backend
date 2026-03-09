<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->string('correction_file_path')->nullable()->after('observation');
        });

        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->string('correction_file_path')->nullable()->after('observation');
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropColumn('correction_file_path');
        });

        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->dropColumn('correction_file_path');
        });
    }
};
