<?php

use Illuminate\Support\Facades\Route;

/**
 * Student Dashboard Routes
 * Prefix: /api/student
 */

Route::get('/dashboard', function () {
    return response()->json([
        'message' => 'Student Dashboard',
        'role' => 'student'
    ]);
})->middleware('auth:api');
