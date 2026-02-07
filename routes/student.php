<?php

use Illuminate\Support\Facades\Route;

/**
 * Student API Routes
 * 
 * All routes here are automatically prefixed with /api/student
 * Example: http://localhost:8000/api/student/courses
 */

// Authentication routes (public)
Route::post('/login', [App\Http\Controllers\Student\AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Student\AuthController::class, 'logout']);
    
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Student Dashboard',
            'role' => 'student'
        ]);
    });
});
