<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserManagementController;

/**
 * Admin User Management Routes
 * Prefix: /api/admin
 */

// Teacher management
Route::post('/teachers', [UserManagementController::class, 'createTeacher'])->middleware('auth:api');
Route::get('/teachers', [UserManagementController::class, 'listTeachers'])->middleware('auth:api');
Route::delete('/teachers/{id}', [UserManagementController::class, 'deleteTeacher'])->middleware('auth:api');

// Student management
Route::post('/students', [UserManagementController::class, 'createStudent'])->middleware('auth:api');
Route::get('/students', [UserManagementController::class, 'listStudents'])->middleware('auth:api');
Route::delete('/students/{id}', [UserManagementController::class, 'deleteStudent'])->middleware('auth:api');
