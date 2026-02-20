<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GroupController;

/**
 * Admin Group Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Group CRUD operations
Route::get('/groups', [GroupController::class, 'index'])->middleware('auth:api');
Route::post('/groups', [GroupController::class, 'store'])->middleware('auth:api');

// Group schedule options - must be before the {id} route to avoid conflicts
Route::get('/groups/schedule/options', [GroupController::class, 'getScheduleOptions'])->middleware('auth:api');

// Group detail routes
Route::get('/groups/{id}', [GroupController::class, 'show'])->middleware('auth:api');
Route::put('/groups/{id}', [GroupController::class, 'update'])->middleware('auth:api');
Route::delete('/groups/{id}', [GroupController::class, 'destroy'])->middleware('auth:api');

// Add/Remove students from groups
Route::post('/groups/{id}/students', [GroupController::class, 'addStudent'])->middleware('auth:api');
Route::delete('/groups/{groupId}/students/{studentId}', [GroupController::class, 'removeStudent'])->middleware('auth:api');

// Update group members
Route::put('/groups/{id}/members', [GroupController::class, 'updateGroupMembers'])->middleware('auth:api');
