<?php

use App\Http\Controllers\Admin\EventController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Event Management Routes
 * Prefix: /api/admin
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::get('/events/{id}/attendance',        [EventController::class, 'getAttendance']);
    Route::post('/events/{id}/create-zoom-meeting', [EventController::class, 'createZoomMeeting']);
    Route::post('/events/{id}/recur-monthly', [EventController::class, 'recurMonthly']);
});
