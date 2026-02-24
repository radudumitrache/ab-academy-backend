<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Student;
use App\Models\Admin;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminChatController extends Controller
{
    /**
     * Admin-initiated chat creation.
     * The authenticated admin specifies which student to open a conversation with.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can use this endpoint'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $student = Student::find($request->student_id);
        if (!$student) {
            return response()->json(['message' => 'Invalid student ID'], 422);
        }

        $existingChat = Chat::where('admin_id', $user->id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($existingChat) {
            if (!$existingChat->is_active) {
                $existingChat->update(['is_active' => true]);
            }

            return response()->json([
                'message' => 'Chat already exists',
                'chat'    => $existingChat->load(['admin', 'student', 'messages.sender']),
            ]);
        }

        $chat = Chat::create([
            'admin_id'        => $user->id,
            'student_id'      => $request->student_id,
            'teacher_id'      => null,
            'last_message_at' => now(),
            'is_active'       => true,
        ]);

        return response()->json([
            'message' => 'Chat created successfully',
            'chat'    => $chat->load(['admin', 'student']),
        ], 201);
    }

    /**
     * Send a message in an admin–student chat.
     * Both the student and the admin belonging to the chat are authorised.
     */
    public function sendMessage(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $chat = Chat::find($chatId);

        if (!$chat) {
            return response()->json([
                'message' => 'Chat not found'
            ], 404);
        }

        $user = Auth::user();

        if (($user->isStudent() && $chat->student_id != $user->id) ||
            ($user->isAdmin()   && $chat->admin_id   != $user->id)) {
            return response()->json([
                'message' => 'Unauthorized to send message in this chat'
            ], 403);
        }

        $message = Message::create([
            'chat_id'     => $chat->id,
            'content'     => $request->content,
            'sender_id'   => $user->id,
            'sender_type' => get_class($user),
        ]);

        $chat->update(['last_message_at' => now()]);

        // Pass both $chat and $message — MessageSent requires both arguments
        broadcast(new MessageSent($chat, $message))->toOthers();

        return response()->json([
            'message'      => 'Message sent successfully',
            'chat_message' => $message->load('sender')
        ]);
    }
}
