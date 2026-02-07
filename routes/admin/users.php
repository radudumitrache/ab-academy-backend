<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserManagementController;

/**
 * Admin User Management Routes
 * Prefix: /api/admin
 */

Route::middleware('auth:api')->group(function () {
    // Teacher management
    Route::post('/teachers', [UserManagementController::class, 'createTeacher']);
    Route::get('/teachers', [UserManagementController::class, 'listTeachers']);
    Route::delete('/teachers/{id}', [UserManagementController::class, 'deleteTeacher']);

    // Student management
    Route::post('/students', [UserManagementController::class, 'createStudent']);
    Route::get('/students', [UserManagementController::class, 'listStudents']);
    Route::delete('/students/{id}', [UserManagementController::class, 'deleteStudent']);
});
