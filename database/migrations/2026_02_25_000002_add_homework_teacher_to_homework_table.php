<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->unsignedBigInteger('homework_teacher')->nullable()->after('id');
            $table->foreign('homework_teacher')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->dropForeign(['homework_teacher']);
            $table->dropColumn('homework_teacher');
        });
    }
};
