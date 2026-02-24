<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminChatController;

/*
|--------------------------------------------------------------------------
| Chat API Routes
|--------------------------------------------------------------------------
|
| These routes are used for the chat functionality between teachers and students.
| They are protected by the auth:api middleware to ensure only authenticated
| users can access them.
|
*/

// Chat routes
Route::middleware('auth:api')->group(function () {
    // Get all chats for the authenticated user
    Route::get('/chats', [ChatController::class, 'index']);
    
    // Create a new chat
    Route::post('/chats', [ChatController::class, 'store']);
    
    // Get a specific chat with messages
    Route::get('/chats/{id}', [ChatController::class, 'show']);
    
    // Send a message in a chat
    Route::post('/chats/{id}/messages', [AdminChatController::class, 'sendMessage']);
    
    // Get unread message count
    Route::get('/chats/unread/count', [ChatController::class, 'unreadCount']);
    
    // Archive a chat
    Route::put('/chats/{id}/archive', [ChatController::class, 'archive']);
    
    // Admin Chat Routes
    // Create a new admin chat
    Route::post('/admin-chats', [AdminChatController::class, 'store']);
    
    // Send a message in an admin chat
    Route::post('/admin-chats/{id}/messages', [AdminChatController::class, 'sendMessage']);
});

// Broadcasting authentication route
Route::middleware('auth:api')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});
