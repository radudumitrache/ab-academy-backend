<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Authentication Routes
 * Prefix: /api/admin
 */

// Test route to check if admin routes are working
Route::get('/test', function() {
    return response()->json(['message' => 'Admin routes are working!']);
});

Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);

Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:api');
