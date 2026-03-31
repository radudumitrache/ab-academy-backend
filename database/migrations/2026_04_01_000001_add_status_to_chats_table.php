<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
        });

        // Backfill: archived rows (is_active = false) get status = 'archived'
        DB::table('chats')->where('is_active', false)->update(['status' => 'archived']);
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
