<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_sections', function (Blueprint $table) {
            // Column may already exist from a previous partial run; only add if missing.
            if (!Schema::hasColumn('homework_sections', 'audio_material_id')) {
                $table->unsignedBigInteger('audio_material_id')->nullable()->after('audio_url');
            }
            $table->foreign('audio_material_id')->references('material_id')->on('materials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homework_sections', function (Blueprint $table) {
            $table->dropForeign(['audio_material_id']);
            $table->dropColumn('audio_material_id');
        });
    }
};
