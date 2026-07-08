<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Widen to text so we can store JSON strings
        DB::statement('ALTER TABLE test_multiple_choice_questions MODIFY correct_variant TEXT NOT NULL');
        // Step 2: Wrap existing bare integers into single-element JSON arrays
        DB::statement("UPDATE test_multiple_choice_questions SET correct_variant = CONCAT('[', correct_variant, ']')");
        // Step 3: Tighten back to proper JSON column
        DB::statement('ALTER TABLE test_multiple_choice_questions MODIFY correct_variant JSON NOT NULL');
    }

    public function down(): void
    {
        Schema::table('test_multiple_choice_questions', function (Blueprint $table) {
            $table->integer('correct_variant')->default(0)->change();
        });
    }
};
