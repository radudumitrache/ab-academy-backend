<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('meeting_account_id')
                ->nullable()
                ->after('event_notes')
                ->constrained('meeting_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['meeting_account_id']);
            $table->dropColumn('meeting_account_id');
        });
    }
};
