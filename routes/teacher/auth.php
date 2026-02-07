<?php

use Illuminate\Support\Facades\Route;

/**
 * Teacher Authentication Routes
 * Prefix: /api/teacher
 */

Route::post('/login', [App\Http\Controllers\Teacher\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Teacher\AuthController::class, 'logout']);
});
