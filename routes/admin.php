<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin API Routes
 * 
 * All routes here are automatically prefixed with /api/admin
 * Example: http://localhost:8000/api/admin/users
 */

// Authentication routes (public)
Route::post('/login', [App\Http\Controllers\Admin\AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout']);
    
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Admin Dashboard',
            'role' => 'admin'
        ]);
    });
});
