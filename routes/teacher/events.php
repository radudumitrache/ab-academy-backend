<?php

use App\Http\Controllers\Teacher\EventController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Event Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/events',         [EventController::class, 'index']);
    Route::post('/events',        [EventController::class, 'store']);
    Route::get('/events/{id}',    [EventController::class, 'show']);
    Route::put('/events/{id}',    [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
});
