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
        // First, make sure there are no old tables
        Schema::dropIfExists('chats_old');
        Schema::dropIfExists('messages_old');
        
        // Check if the chats table exists
        if (Schema::hasTable('chats')) {
            // Get existing data
            $chats = DB::table('chats')->get();
            
            // Drop the table completely
            Schema::dropIfExists('chats');
            
            // Recreate the table with the correct structure
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('teacher_id');
                $table->timestamp('last_message_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                // Add foreign keys
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
            
            // Reinsert the data
            foreach ($chats as $chat) {
                $newData = [
                    'id' => $chat->id,
                    'student_id' => $chat->student_id ?? $chat->student_recipient ?? null,
                    'teacher_id' => $chat->teacher_id ?? $chat->admin_recipient ?? null,
                    'last_message_at' => $chat->last_message_at ?? $chat->date_created ?? now(),
                    'is_active' => $chat->is_active ?? true,
                    'created_at' => $chat->created_at ?? now(),
                    'updated_at' => $chat->updated_at ?? now(),
                ];
                
                // Only insert if we have valid student and teacher IDs
                if ($newData['student_id'] && $newData['teacher_id']) {
                    DB::table('chats')->insert($newData);
                }
            }
        } else {
            // If the table doesn't exist, just create it
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('teacher_id');
                $table->timestamp('last_message_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                // Add foreign keys
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
        
        // Now handle the messages table
        if (Schema::hasTable('messages')) {
            // Get existing data
            $messages = DB::table('messages')->get();
            
            // Drop the table completely
            Schema::dropIfExists('messages');
            
            // Recreate the table with the correct structure
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_id');
                $table->text('content');
                $table->unsignedBigInteger('sender_id');
                $table->string('sender_type');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                // Add foreign key
                $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            });
            
            // Reinsert the data
            foreach ($messages as $message) {
                $newData = [
                    'id' => $message->id,
                    'chat_id' => $message->chat_id,
                    'content' => $message->content ?? $message->message ?? $message->message_text ?? '',
                    'sender_id' => $message->sender_id ?? $message->message_author ?? null,
                    'sender_type' => $message->sender_type ?? 'App\\Models\\User',
                    'read_at' => $message->read_at ?? null,
                    'created_at' => $message->created_at ?? now(),
                    'updated_at' => $message->updated_at ?? now(),
                ];
                
                // Only insert if we have valid chat_id and content
                if ($newData['chat_id'] && !empty($newData['content'])) {
                    DB::table('messages')->insert($newData);
                }
            }
        } else {
            // If the table doesn't exist, just create it
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_id');
                $table->text('content');
                $table->unsignedBigInteger('sender_id');
                $table->string('sender_type');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                
                // Add foreign key
                $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
    }
};
