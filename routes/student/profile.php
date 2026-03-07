<?php

use App\Http\Controllers\Student\ProfileController;
use Illuminate\Support\Facades\Route;

/**
 * Student Profile Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/profile',                  [ProfileController::class, 'show']);
    Route::put('/profile',                  [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
});
