<?php

use Illuminate\Support\Facades\Route;

/**
 * Teacher API Routes
 * 
 * All routes here are automatically prefixed with /api/teacher
 * Example: http://localhost:8000/api/teacher/courses
 */

// Authentication routes (public)
Route::post('/login', [App\Http\Controllers\Teacher\AuthController::class, 'login']);

// Protected routes (require authentication)
Route::post('/logout', [App\Http\Controllers\Teacher\AuthController::class, 'logout'])->middleware('auth:api');

Route::get('/dashboard', function () {
    return response()->json([
        'message' => 'Teacher Dashboard',
        'role' => 'teacher'
    ]);
})->middleware('auth:api');
