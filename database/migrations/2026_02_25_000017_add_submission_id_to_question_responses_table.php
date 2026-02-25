<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            // Link each answer back to its homework submission
            $table->unsignedBigInteger('submission_id')->nullable()->after('response_id');

            $table->foreign('submission_id')->references('id')->on('homework_submissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropColumn('submission_id');
        });
    }
};
