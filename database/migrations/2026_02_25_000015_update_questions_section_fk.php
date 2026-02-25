<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Drop the old loose integer + string pair
            $table->dropColumn('section_type');

            // Make section_id a proper FK to homework_sections
            // (set nullable so it can be changed in place without touching existing nulls)
            $table->foreign('section_id')->references('id')->on('homework_sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->string('section_type')->nullable()->after('section_id');
        });
    }
};
