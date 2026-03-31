<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('groups_assigned');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });
    }
};
