<?php

use App\Http\Controllers\Admin\StudentPerformanceController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Student Performance Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

// All students
Route::get('/students/performance', [StudentPerformanceController::class, 'index']);

// Single student
Route::get('/students/{id}/performance', [StudentPerformanceController::class, 'show']);
