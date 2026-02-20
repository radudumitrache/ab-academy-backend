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
        // First, check if chats_old table exists and drop it
        if (Schema::hasTable('chats_old')) {
            // Disable foreign key checks to allow dropping the table
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Schema::dropIfExists('chats_old');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Now check if we need to update the chats table
        if (Schema::hasTable('chats')) {
            // Check if the table already has the new columns
            $hasNewColumns = Schema::hasColumn('chats', 'student_id') && 
                            Schema::hasColumn('chats', 'teacher_id') && 
                            Schema::hasColumn('chats', 'last_message_at');
            
            if (!$hasNewColumns) {
                // Get existing data
                $chats = DB::table('chats')->get();
                
                // Create a temporary table with the new structure
                Schema::create('chats_new', function (Blueprint $table) {
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
                        'id' => $chat->id ?? null,
                        'student_id' => $chat->student_recipient ?? null,
                        'teacher_id' => $chat->admin_recipient ?? null,
                        'last_message_at' => $chat->date_created ?? now(),
                        'is_active' => true,
                        'created_at' => $chat->created_at ?? now(),
                        'updated_at' => $chat->updated_at ?? now(),
                    ];
                    
                    // Only insert if we have valid student and teacher IDs
                    if ($newData['id'] && $newData['student_id'] && $newData['teacher_id']) {
                        DB::table('chats_new')->insert($newData);
                    }
                }
                
                // Drop the old table
                Schema::dropIfExists('chats');
                
                // Rename the new table to chats
                Schema::rename('chats_new', 'chats');
            }
        } else {
            // If the chats table doesn't exist, just create it
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
        // No need to reverse this migration as it's just a cleanup
    }
};
