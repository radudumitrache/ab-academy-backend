<?php

use App\Http\Controllers\Teacher\NotificationController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Notification Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    // must come before /{id} routes to avoid conflict
    Route::put('/notifications/seen-all',      [NotificationController::class, 'markAllSeen']);

    Route::get('/notifications',               [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/seen',     [NotificationController::class, 'markSeen']);
    Route::delete('/notifications/{id}',       [NotificationController::class, 'destroy']);
});
