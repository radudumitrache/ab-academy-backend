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
     *
     * - If the authenticated user is a student: creates a chat between that
     *   student and the default admin (from env DEFAULT_ADMIN_ID or first admin).
     * - If the authenticated user is an admin: creates a chat between themselves
     *   and the student specified in `student_id`.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            // Admin initiates the conversation — student_id is required
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $student = Student::find($request->student_id);
            if (!$student) {
                return response()->json([
                    'message' => 'Invalid student ID'
                ], 422);
            }

            $adminId   = $user->id;
            $studentId = $request->student_id;

        } elseif ($user->isStudent()) {
            // Student initiates — use their own ID and look up the default admin
            $studentId = $user->id;

            $adminId = config('chat.default_admin_id', env('DEFAULT_ADMIN_ID'));

            if (!$adminId) {
                $admin = Admin::first();
                if (!$admin) {
                    return response()->json([
                        'message' => 'No admin available for chat'
                    ], 422);
                }
                $adminId = $admin->id;
            } else {
                $admin = Admin::find($adminId);
                if (!$admin) {
                    return response()->json([
                        'message' => 'Configured admin not found'
                    ], 422);
                }
            }

        } else {
            return response()->json([
                'message' => 'Only students or admins can create chats'
            ], 403);
        }

        // Reuse an existing chat if one already exists between these two users
        $existingChat = Chat::where('admin_id', $adminId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingChat) {
            if (!$existingChat->is_active) {
                $existingChat->update(['is_active' => true]);
            }

            return response()->json([
                'message' => 'Chat already exists',
                'chat' => $existingChat->load(['admin', 'student', 'messages.sender'])
            ]);
        }

        $chat = Chat::create([
            'admin_id'        => $adminId,
            'student_id'      => $studentId,
            'teacher_id'      => null,
            'last_message_at' => now(),
            'is_active'       => true,
        ]);

        return response()->json([
            'message' => 'Chat created successfully',
            'chat'    => $chat->load(['admin', 'student'])
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
