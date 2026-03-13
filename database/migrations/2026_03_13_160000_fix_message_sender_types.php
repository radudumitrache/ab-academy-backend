<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix messages where sender_type is App\Models\User by looking up the user's role
        $messages = DB::table('messages')
            ->where('sender_type', 'App\\Models\\User')
            ->get();

        foreach ($messages as $message) {
            $user = DB::table('users')->where('id', $message->sender_id)->first();
            if (!$user) {
                continue;
            }

            $correctType = match ($user->role) {
                'admin'   => 'App\\Models\\Admin',
                'student' => 'App\\Models\\Student',
                'teacher' => 'App\\Models\\Teacher',
                default   => 'App\\Models\\User',
            };

            DB::table('messages')
                ->where('id', $message->id)
                ->update(['sender_type' => $correctType]);
        }
    }

    public function down(): void
    {
        DB::table('messages')
            ->whereIn('sender_type', [
                'App\\Models\\Admin',
                'App\\Models\\Student',
                'App\\Models\\Teacher',
            ])
            ->update(['sender_type' => 'App\\Models\\User']);
    }
};
