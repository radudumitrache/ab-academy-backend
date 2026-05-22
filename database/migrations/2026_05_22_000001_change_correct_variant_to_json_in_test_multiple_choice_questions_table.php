<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wrap existing integer values into JSON arrays before changing column type
        DB::statement('UPDATE test_multiple_choice_questions SET correct_variant = JSON_ARRAY(correct_variant)');

        Schema::table('test_multiple_choice_questions', function (Blueprint $table) {
            $table->json('correct_variant')->change();
        });
    }

    public function down(): void
    {
        Schema::table('test_multiple_choice_questions', function (Blueprint $table) {
            $table->integer('correct_variant')->default(0)->change();
        });
    }
};
