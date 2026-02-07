<?php

use Illuminate\Support\Facades\Route;

/**
 * Student Dashboard Routes
 * Prefix: /api/student
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Student Dashboard',
            'role' => 'student'
        ]);
    });
});
