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
        // This migration is designed to fix the foreign key constraint issue on MySQL
        
        // First, check if we have the temporary chats_old table
        if (Schema::hasTable('chats_old')) {
            // Check if the messages table exists and has a foreign key to chats
            if (Schema::hasTable('messages')) {
                // Disable foreign key checks temporarily
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                // Get any messages that reference the old chats table
                $messages = DB::table('messages')
                    ->select('id', 'chat_id')
                    ->get();
                
                // Update message references to use the new chats table IDs
                foreach ($messages as $message) {
                    // Check if the chat exists in the new table
                    $chatExists = DB::table('chats')
                        ->where('id', $message->chat_id)
                        ->exists();
                    
                    if (!$chatExists) {
                        // If the chat doesn't exist in the new table, create a placeholder
                        // Find the original chat data
                        $oldChat = DB::table('chats_old')
                            ->where('id', $message->chat_id)
                            ->first();
                        
                        if ($oldChat) {
                            // Insert a placeholder chat
                            DB::table('chats')->insert([
                                'id' => $message->chat_id,
                                'student_id' => $oldChat->student_recipient ?? 1, // Fallback to ID 1
                                'teacher_id' => $oldChat->admin_recipient ?? 1, // Fallback to ID 1
                                'last_message_at' => now(),
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            // If we can't find the original chat, delete the orphaned message
                            DB::table('messages')->where('id', $message->id)->delete();
                        }
                    }
                }
                
                // Now we can safely drop the old table
                Schema::dropIfExists('chats_old');
                
                // Re-enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } else {
                // If messages table doesn't exist, we can safely drop chats_old
                Schema::dropIfExists('chats_old');
            }
        }
        
        // Also check for messages_old table and clean it up
        if (Schema::hasTable('messages_old')) {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Drop the old messages table
            Schema::dropIfExists('messages_old');
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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
