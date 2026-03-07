<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Chat Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 *
 * Admins can view all their student chats, send messages, and open new
 * conversations with any student.
 */

// List all chats for this admin (most recent first, with last message preview)
Route::get('/chats',                [ChatController::class, 'index']);

// Get a specific chat with full message history (marks messages as read)
Route::get('/chats/{id}',           [ChatController::class, 'show']);

// Send a message in a chat (admin must own the chat)
Route::post('/chats/{id}/messages', [AdminChatController::class, 'sendMessage']);

// Unread message count across all admin chats
Route::get('/chats/unread/count',   [ChatController::class, 'unreadCount']);

// Open a new chat with a student (or re-activate an existing one)
Route::post('/chats/student',       [AdminChatController::class, 'store']);

// Archive a chat
Route::put('/chats/{id}/archive',   [ChatController::class, 'archive']);
