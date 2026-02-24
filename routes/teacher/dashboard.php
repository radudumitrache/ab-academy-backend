<?php

use App\Http\Controllers\Dashboard\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Dashboard Routes
 * Prefix: /api/teacher
 */

Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->middleware('auth:api');
