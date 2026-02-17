<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin Dashboard Routes
 * Prefix: /api/admin
 */

Route::get('/dashboard', function () {
    return response()->json([
        'message' => 'Admin Dashboard',
        'user' => auth()->user(),
        'role' => 'admin'
    ]);
})->middleware('auth:api');
