<?php

use Illuminate\Support\Facades\Route;

/**
 * Teacher Dashboard Routes
 * Prefix: /api/teacher
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Teacher Dashboard',
            'role' => 'teacher'
        ]);
    });
});
