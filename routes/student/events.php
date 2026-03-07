<?php

use App\Http\Controllers\Student\EventController;
use Illuminate\Support\Facades\Route;

/**
 * Student Event Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/events',      [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
});
