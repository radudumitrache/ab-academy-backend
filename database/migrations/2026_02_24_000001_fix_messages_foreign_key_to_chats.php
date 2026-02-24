<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // This migration only applies to MySQL — the broken FK constraint
        // (messages.chat_id → chats_old) only exists in the MySQL production DB.
        // SQLite handles foreign keys differently and does not have this issue.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop the stale FK that references chats_old, then add the correct one.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('messages', function (Blueprint $table) {
            if ($this->foreignKeyExists('messages', 'messages_chat_id_foreign')) {
                $table->dropForeign('messages_chat_id_foreign');
            }
        });

        // Drop the chats_old table if it still exists
        Schema::dropIfExists('chats_old');

        Schema::table('messages', function (Blueprint $table) {
            if (!$this->foreignKeyExists('messages', 'messages_chat_id_foreign')) {
                $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // Nothing to reverse — the old state was broken.
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraintName]
        );

        return !empty($result);
    }
};
