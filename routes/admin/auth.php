<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Authentication Routes
 * Prefix: /api/admin
 */

Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout']);
});
