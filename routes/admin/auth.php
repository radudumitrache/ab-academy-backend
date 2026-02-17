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

// Use fully qualified namespace to avoid any namespace issues
use App\Http\Controllers\Admin\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:api');
