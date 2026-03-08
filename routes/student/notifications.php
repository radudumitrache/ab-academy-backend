<?php

use App\Http\Controllers\Student\NotificationController;
use Illuminate\Support\Facades\Route;

/**
 * Student Notification Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/notifications',               [NotificationController::class, 'index']);
    Route::put('/notifications/seen-all',      [NotificationController::class, 'markAllSeen']);
    Route::put('/notifications/{id}/seen',     [NotificationController::class, 'markSeen']);
    Route::delete('/notifications/{id}',       [NotificationController::class, 'destroy']);
});
