<?php

use Illuminate\Support\Facades\Route;

/**
 * Teacher Dashboard Routes
 * Prefix: /api/teacher
 */

Route::get('/dashboard', function () {
    return response()->json([
        'message' => 'Teacher Dashboard',
        'role' => 'teacher'
    ]);
})->middleware('auth:api');
