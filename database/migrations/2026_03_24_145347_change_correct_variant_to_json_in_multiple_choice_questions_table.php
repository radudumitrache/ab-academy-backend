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
        Schema::table('multiple_choice_questions', function (Blueprint $table) {
            $table->json('correct_variant')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multiple_choice_questions', function (Blueprint $table) {
            $table->integer('correct_variant')->change();
        });
    }
};
