<?php

use App\Http\Controllers\Student\ScheduleController;
use Illuminate\Support\Facades\Route;

/**
 * Student Schedule Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/schedule', [ScheduleController::class, 'index']);
});
