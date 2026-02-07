<?php

use Illuminate\Support\Facades\Route;

/**
 * Student Authentication Routes
 * Prefix: /api/student
 */

Route::post('/login', [App\Http\Controllers\Student\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Student\AuthController::class, 'logout']);
});
