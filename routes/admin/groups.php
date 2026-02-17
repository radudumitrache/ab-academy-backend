<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Groups\GroupController;
use App\Http\Controllers\Groups\GroupMemberController;

/**
 * Admin Group Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Group CRUD operations
Route::get('/groups', [GroupController::class, 'index'])->middleware('auth:api');
Route::post('/groups', [GroupController::class, 'store'])->middleware('auth:api');
Route::get('/groups/{id}', [GroupController::class, 'show'])->middleware('auth:api');
Route::put('/groups/{id}', [GroupController::class, 'update'])->middleware('auth:api');
Route::delete('/groups/{id}', [GroupController::class, 'destroy'])->middleware('auth:api');

// Add/Remove students from groups
Route::post('/groups/{id}/students', [GroupMemberController::class, 'addStudent'])->middleware('auth:api');
Route::delete('/groups/{groupId}/students/{studentId}', [GroupMemberController::class, 'removeStudent'])->middleware('auth:api');

// Update group members
Route::put('/groups/{id}/group-members', [GroupMemberController::class, 'updateGroupMembers'])->middleware('auth:api');
