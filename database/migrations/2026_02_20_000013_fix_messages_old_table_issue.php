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
        // First, check if messages_old table exists and drop it
        if (Schema::hasTable('messages_old')) {
            // Disable foreign key checks to allow dropping the table
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Schema::dropIfExists('messages_old');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Now check if we need to update the messages table
        if (Schema::hasTable('messages')) {
            // Check if the table already has the new columns
            $hasNewColumns = Schema::hasColumn('messages', 'content') && 
                            Schema::hasColumn('messages', 'sender_id') && 
                            Schema::hasColumn('messages', 'sender_type');
            
            if (!$hasNewColumns) {
                // Get existing data
                $messages = DB::table('messages')->get();
                
                // Create a temporary table with the new structure
                Schema::create('messages_new', function (Blueprint $table) {
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
                        'id' => $message->id ?? null,
                        'chat_id' => $message->chat_id ?? null,
                        'content' => $message->message ?? $message->message_text ?? '',
                        'sender_id' => $message->message_author ?? null,
                        'sender_type' => 'App\\Models\\User',
                        'read_at' => null,
                        'created_at' => $message->created_at ?? now(),
                        'updated_at' => $message->updated_at ?? now(),
                    ];
                    
                    // Only insert if we have valid chat_id and content
                    if ($newData['id'] && $newData['chat_id'] && !empty($newData['content'])) {
                        DB::table('messages_new')->insert($newData);
                    }
                }
                
                // Drop the old table
                Schema::dropIfExists('messages');
                
                // Rename the new table to messages
                Schema::rename('messages_new', 'messages');
            }
        } else {
            // If the messages table doesn't exist, just create it
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
        // No need to reverse this migration as it's just a cleanup
    }
};
