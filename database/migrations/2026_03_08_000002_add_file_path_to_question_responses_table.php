<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            // Nullable so text-only answers remain valid
            $table->string('file_path')->nullable()->after('answer');
            // Also make answer nullable so file-only submissions are valid
            $table->text('answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropColumn('file_path');
            $table->text('answer')->nullable(false)->change();
        });
    }
};
