<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table instead of altering it
        if (Schema::hasTable('messages')) {
            // Get existing data
            $messages = DB::table('messages')->get();
            
            // Rename the old table
            Schema::rename('messages', 'messages_old');
            
            // Create new table with desired schema
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->unsignedBigInteger('sender_id');
                $table->string('sender_type');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
            
            // Migrate data from old table to new table if needed
            foreach ($messages as $message) {
                $newData = [
                    'id' => $message->id,
                    'chat_id' => $message->chat_id,
                    'content' => $message->message ?? $message->message_text ?? '',
                    'sender_id' => $message->message_author ?? null,
                    'sender_type' => 'App\\Models\\User',
                    'read_at' => null,
                    'created_at' => $message->created_at ?? now(),
                    'updated_at' => $message->updated_at ?? now(),
                ];
                
                // Only insert if we have valid chat_id and content
                if ($newData['chat_id'] && !empty($newData['content'])) {
                    DB::table('messages')->insert($newData);
                }
            }
            
            // Drop the old table
            Schema::dropIfExists('messages_old');
        } else {
            // If the table doesn't exist, just create it
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->unsignedBigInteger('sender_id');
                $table->string('sender_type');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn(['content', 'sender_id', 'sender_type', 'read_at']);
                $table->text('message')->nullable();
                $table->string('author')->nullable();
                $table->text('message_text')->nullable();
                $table->unsignedBigInteger('message_author')->nullable();
            });
        }
    }
};
