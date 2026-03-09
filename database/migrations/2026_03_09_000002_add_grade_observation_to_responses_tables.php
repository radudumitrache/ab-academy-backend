<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('file_path');
            $table->text('observation')->nullable()->after('grade');
        });

        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('answer');
            $table->text('observation')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropColumn(['grade', 'observation']);
        });

        Schema::table('test_question_responses', function (Blueprint $table) {
            $table->dropColumn(['grade', 'observation']);
        });
    }
};
