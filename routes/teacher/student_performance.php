<?php

use App\Http\Controllers\Teacher\StudentPerformanceController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Student Performance Routes
 * Prefix: /api/teacher
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    // All students across teacher's groups (owned + assisted)
    Route::get('/students/performance', [StudentPerformanceController::class, 'index']);

    // Single student (must belong to teacher's groups)
    Route::get('/students/{id}/performance', [StudentPerformanceController::class, 'show']);
});
