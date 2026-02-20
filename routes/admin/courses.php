<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CourseController;

/**
 * Admin Course Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Course CRUD operations
Route::get('/courses', [CourseController::class, 'index'])->middleware('auth:api');
Route::post('/courses', [CourseController::class, 'store'])->middleware('auth:api');
Route::get('/courses/{id}', [CourseController::class, 'show'])->middleware('auth:api');
Route::put('/courses/{id}', [CourseController::class, 'update'])->middleware('auth:api');
Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->middleware('auth:api');
