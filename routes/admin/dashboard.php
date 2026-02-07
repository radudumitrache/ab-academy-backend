<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Dashboard Routes
 * Prefix: /api/admin
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Admin Dashboard',
            'role' => 'admin'
        ]);
    });
});
