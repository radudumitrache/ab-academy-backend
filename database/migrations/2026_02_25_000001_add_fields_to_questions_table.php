<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->json('instruction_files')->nullable()->after('question_type');
            $table->unsignedInteger('order')->nullable()->after('instruction_files');
            $table->unsignedBigInteger('section_id')->nullable()->after('order');
            $table->string('section_type')->nullable()->after('section_id'); // 'reading' or 'listening'
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['instruction_files', 'order', 'section_id', 'section_type']);
        });
    }
};
