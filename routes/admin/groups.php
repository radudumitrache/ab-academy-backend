<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GroupController;

/**
 * Admin Group Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);

    // Must be before /{id} to avoid route conflicts
    Route::get('/groups/schedule/options', [GroupController::class, 'getScheduleOptions']);

    Route::get('/groups/{id}', [GroupController::class, 'show']);
    Route::put('/groups/{id}', [GroupController::class, 'update']);
    Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

    Route::post('/groups/{id}/students', [GroupController::class, 'addStudent']);
    Route::post('/groups/{id}/students/by-username', [GroupController::class, 'addStudentByUsername']);
    Route::delete('/groups/{groupId}/students/{studentId}', [GroupController::class, 'removeStudent']);

    Route::put('/groups/{id}/members', [GroupController::class, 'updateGroupMembers']);
    Route::post('/groups/{id}/generate-code', [GroupController::class, 'generateCode']);
});
