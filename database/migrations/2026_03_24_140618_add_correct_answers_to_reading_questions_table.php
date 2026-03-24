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
        Schema::table('reading_questions', function (Blueprint $table) {
            $table->json('correct_answers')->nullable()->after('sample_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reading_questions', function (Blueprint $table) {
            $table->dropColumn('correct_answers');
        });
    }
};
