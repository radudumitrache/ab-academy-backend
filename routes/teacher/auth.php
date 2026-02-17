<?php

use Illuminate\Support\Facades\Route;

/**
 * Teacher Authentication Routes
 * Prefix: /api/teacher
 */

Route::post('/login', [App\Http\Controllers\Teacher\AuthController::class, 'login']);

Route::post('/logout', [App\Http\Controllers\Teacher\AuthController::class, 'logout'])->middleware('auth:api');
