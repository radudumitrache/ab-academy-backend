<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_acquisitions', function (Blueprint $table) {
            $table->unsignedInteger('remaining_courses')->nullable()->after('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('product_acquisitions', function (Blueprint $table) {
            $table->dropColumn('remaining_courses');
        });
    }
};
