<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('date_created');
        });
    }

    public function down(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
