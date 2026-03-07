<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/**
 * Student Chat Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/chats',                [ChatController::class, 'index']);
    Route::get('/chats/{id}',           [ChatController::class, 'show']);
    Route::post('/chats/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/chats/unread/count',   [ChatController::class, 'unreadCount']);
    Route::post('/chats/admin',         [AdminChatController::class, 'store']);
});
