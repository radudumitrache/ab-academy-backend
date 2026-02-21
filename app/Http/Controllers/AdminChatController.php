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
     * Create a new chat between a student and an admin.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the student
        $student = Student::find($request->student_id);
        if (!$student) {
            return response()->json([
                'message' => 'Invalid student ID'
            ], 422);
        }

        // Get the default admin ID from config or env
        $adminId = config('chat.default_admin_id', env('DEFAULT_ADMIN_ID'));
        
        // If no admin ID is configured, use the first admin in the system
        if (!$adminId) {
            $admin = Admin::first();
            if (!$admin) {
                return response()->json([
                    'message' => 'No admin available for chat'
                ], 422);
            }
            $adminId = $admin->id;
        } else {
            // Verify the admin exists
            $admin = Admin::find($adminId);
            if (!$admin) {
                return response()->json([
                    'message' => 'Configured admin not found'
                ], 422);
            }
        }

        // Check if a chat already exists between these users
        $existingChat = Chat::where('admin_id', $adminId)
            ->where('student_id', $request->student_id)
            ->first();

        if ($existingChat) {
            // If chat exists but is inactive, reactivate it
            if (!$existingChat->is_active) {
                $existingChat->update(['is_active' => true]);
            }
            
            return response()->json([
                'message' => 'Chat already exists',
                'chat' => $existingChat->load(['admin', 'student'])
            ]);
        }

        // Create a new chat
        $chat = Chat::create([
            'admin_id' => $adminId,
            'student_id' => $request->student_id,
            'teacher_id' => null,
            'last_message_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Chat created successfully',
            'chat' => $chat->load(['admin', 'student'])
        ], 201);
    }

    /**
     * Send a message in an admin chat.
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

        // Check if the user is authorized to send a message in this chat
        $user = Auth::user();
        if (($user->isStudent() && $chat->student_id != $user->id) ||
            ($user->isAdmin() && $chat->admin_id != $user->id)) {
            return response()->json([
                'message' => 'Unauthorized to send message in this chat'
            ], 403);
        }

        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'content' => $request->content,
            'sender_id' => $user->id,
            'sender_type' => get_class($user),
        ]);

        // Update the chat's last_message_at timestamp
        $chat->update(['last_message_at' => now()]);

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'chat_message' => $message->load('sender')
        ]);
    }
}
