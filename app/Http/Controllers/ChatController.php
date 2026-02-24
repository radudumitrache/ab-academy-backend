<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * List all active chats for the authenticated user.
     * Returns the latest message per chat for preview purposes.
     */
    public function index()
    {
        $user  = Auth::user();
        $query = Chat::with([
            'student',
            'admin',
            'messages' => fn($q) => $q->latest()->limit(1),
        ])->active()->orderByDesc('last_message_at');

        if ($user->isStudent()) {
            $query->forStudent($user->id);
        } elseif ($user->isAdmin()) {
            $query->forAdmin($user->id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats'   => $query->get(),
        ]);
    }

    /**
     * Get a specific chat with its full message history.
     * Marks all messages from the other party as read.
     */
    public function show($id)
    {
        $user = Auth::user();
        $chat = Chat::with(['student', 'admin', 'messages.sender'])->find($id);

        if (!$chat) {
            return response()->json(['message' => 'Chat not found'], 404);
        }

        if (($user->isStudent() && $chat->student_id != $user->id) ||
            ($user->isAdmin()   && $chat->admin_id   != $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark incoming messages as read
        $chat->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Chat retrieved successfully',
            'chat'    => $chat,
        ]);
    }

    /**
     * Count unread messages for the authenticated user across all their chats.
     */
    public function unreadCount()
    {
        $user = Auth::user();

        $count = Message::whereHas('chat', function ($q) use ($user) {
            if ($user->isStudent()) {
                $q->where('student_id', $user->id);
            } elseif ($user->isAdmin()) {
                $q->where('admin_id', $user->id);
            }
        })
        ->whereNull('read_at')
        ->where('sender_id', '!=', $user->id)
        ->count();

        return response()->json([
            'message'      => 'Unread count retrieved successfully',
            'unread_count' => $count,
        ]);
    }

    /**
     * Archive a chat (admin only).
     */
    public function archive($id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can archive chats'], 403);
        }

        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['message' => 'Chat not found'], 404);
        }

        if ($chat->admin_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $chat->update(['is_active' => false]);

        return response()->json([
            'message' => 'Chat archived successfully',
            'chat'    => $chat,
        ]);
    }
}
