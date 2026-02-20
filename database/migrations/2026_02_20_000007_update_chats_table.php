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
        if (Schema::hasTable('chats')) {
            // Get existing data
            $chats = DB::table('chats')->get();
            
            // Rename the old table
            Schema::rename('chats', 'chats_old');
            
            // Create new table with desired schema
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('last_message_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            
            // Migrate data from old table to new table if needed
            foreach ($chats as $chat) {
                $newData = [
                    'id' => $chat->id,
                    'student_id' => $chat->student_recipient ?? null,
                    'teacher_id' => $chat->admin_recipient ?? null,
                    'last_message_at' => $chat->date_created ?? now(),
                    'is_active' => true,
                    'created_at' => $chat->created_at ?? now(),
                    'updated_at' => $chat->updated_at ?? now(),
                ];
                
                // Only insert if we have valid student and teacher IDs
                if ($newData['student_id'] && $newData['teacher_id']) {
                    DB::table('chats')->insert($newData);
                }
            }
            
            // Drop the old table
            Schema::dropIfExists('chats_old');
        } else {
            // If the table doesn't exist, just create it
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('last_message_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('chats')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropColumn(['student_id', 'teacher_id', 'last_message_at', 'is_active']);
                $table->unsignedBigInteger('student_recipient')->nullable();
                $table->unsignedBigInteger('admin_recipient')->nullable();
                $table->timestamp('date_created')->nullable();
            });
        }
    }
};
