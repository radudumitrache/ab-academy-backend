<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Student;
use App\Models\Teacher;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get all chats for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $chats = [];

        if ($user->isTeacher()) {
            $chats = Chat::with(['student', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->where('teacher_id', $user->id)
            ->active()
            ->orderBy('last_message_at', 'desc')
            ->get();
        } elseif ($user->isStudent()) {
            $chats = Chat::with(['teacher', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->where('student_id', $user->id)
            ->active()
            ->orderBy('last_message_at', 'desc')
            ->get();
        }

        return response()->json([
            'message' => 'Chats retrieved successfully',
            'chats' => $chats
        ]);
    }

    /**
     * Create a new chat between a teacher and a student.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if the users are actually a teacher and a student
        $teacher = Teacher::find($request->teacher_id);
        $student = Student::find($request->student_id);

        if (!$teacher || !$student) {
            return response()->json([
                'message' => 'Invalid teacher or student ID'
            ], 422);
        }

        // Check if a chat already exists between these users
        $existingChat = Chat::where('teacher_id', $request->teacher_id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($existingChat) {
            // If chat exists but is inactive, reactivate it
            if (!$existingChat->is_active) {
                $existingChat->update(['is_active' => true]);
            }
            
            return response()->json([
                'message' => 'Chat already exists',
                'chat' => $existingChat->load(['teacher', 'student'])
            ]);
        }

        // Create a new chat
        $chat = Chat::create([
            'teacher_id' => $request->teacher_id,
            'student_id' => $request->student_id,
            'last_message_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Chat created successfully',
            'chat' => $chat->load(['teacher', 'student'])
        ], 201);
    }

    /**
     * Get a specific chat with its messages.
     */
    public function show(Request $request, $id)
    {
        $chat = Chat::with(['teacher', 'student'])->findOrFail($id);
        
        // Authorization check
        $user = Auth::user();
        if ($user->id !== $chat->teacher_id && $user->id !== $chat->student_id) {
            return response()->json([
                'message' => 'Unauthorized access to this chat'
            ], 403);
        }

        // Get messages with pagination
        $messages = $chat->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Mark unread messages as read if the authenticated user is the recipient
        if ($user->isTeacher() && $chat->teacher_id === $user->id) {
            $chat->messages()
                ->where('sender_type', Student::class)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } elseif ($user->isStudent() && $chat->student_id === $user->id) {
            $chat->messages()
                ->where('sender_type', Teacher::class)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Chat retrieved successfully',
            'chat' => $chat,
            'messages' => $messages
        ]);
    }

    /**
     * Send a message in a chat.
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

        $chat = Chat::findOrFail($chatId);
        $user = Auth::user();

        // Authorization check
        if ($user->id !== $chat->teacher_id && $user->id !== $chat->student_id) {
            return response()->json([
                'message' => 'Unauthorized access to this chat'
            ], 403);
        }

        // Determine sender type
        $senderType = $user->isTeacher() ? Teacher::class : Student::class;

        // Create the message
        $message = new Message([
            'chat_id' => $chat->id,
            'content' => $request->content,
            'sender_id' => $user->id,
            'sender_type' => $senderType,
        ]);

        $message->save();

        // Update the last_message_at timestamp
        $chat->update(['last_message_at' => now()]);

        // Broadcast the message
        broadcast(new MessageSent($chat, $message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'chat_message' => $message->load('sender')
        ], 201);
    }

    /**
     * Get unread message count for the authenticated user.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = 0;

        if ($user->isTeacher()) {
            $count = Message::whereHas('chat', function ($query) use ($user) {
                $query->where('teacher_id', $user->id);
            })
            ->where('sender_type', Student::class)
            ->whereNull('read_at')
            ->count();
        } elseif ($user->isStudent()) {
            $count = Message::whereHas('chat', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->where('sender_type', Teacher::class)
            ->whereNull('read_at')
            ->count();
        }

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Archive (deactivate) a chat.
     */
    public function archive($id)
    {
        $chat = Chat::findOrFail($id);
        $user = Auth::user();

        // Authorization check
        if ($user->id !== $chat->teacher_id && $user->id !== $chat->student_id) {
            return response()->json([
                'message' => 'Unauthorized access to this chat'
            ], 403);
        }

        $chat->update(['is_active' => false]);

        return response()->json([
            'message' => 'Chat archived successfully'
        ]);
    }
}
