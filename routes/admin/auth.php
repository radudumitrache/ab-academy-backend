<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Authentication Routes
 * Prefix: /api/admin
 */

Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);

Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:api');
