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

// Original login route
// Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);

// Test login route with direct closure
Route::post('/login', function() {
    return response()->json(['message' => 'Login route is working!']);
});

Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->middleware('auth:api');
