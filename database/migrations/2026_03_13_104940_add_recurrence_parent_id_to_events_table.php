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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('recurrence_parent_id')->nullable()->after('meeting_account_id');
            $table->foreign('recurrence_parent_id')->references('id')->on('events')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['recurrence_parent_id']);
            $table->dropColumn('recurrence_parent_id');
        });
    }
};
