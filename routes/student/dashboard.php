<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

/**
 * Student Dashboard Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard',    [DashboardController::class, 'index']);
    Route::get('/achievements', [DashboardController::class, 'achievements']);
});
