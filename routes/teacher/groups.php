<?php

use App\Http\Controllers\Teacher\GroupController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Group Management Routes
 * Prefix: /api/teacher
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    // Schedule options helper — must be before {id} routes
    Route::get('/groups/schedule/options',                   [GroupController::class, 'getScheduleOptions']);

    Route::get('/groups',                                    [GroupController::class, 'index']);
    Route::post('/groups',                                   [GroupController::class, 'store']);
    Route::get('/groups/{id}',                               [GroupController::class, 'show']);
    Route::put('/groups/{id}',                               [GroupController::class, 'update']);
    Route::delete('/groups/{id}',                            [GroupController::class, 'destroy']);
    Route::post('/groups/{id}/students',                     [GroupController::class, 'addStudent']);
    Route::post('/groups/{id}/students/by-username',         [GroupController::class, 'addStudentByUsername']);
    Route::delete('/groups/{groupId}/students/{studentId}',  [GroupController::class, 'removeStudent']);
    Route::post('/groups/{id}/generate-code',                            [GroupController::class, 'generateCode']);
    Route::get('/groups/{id}/attendance',                                [GroupController::class, 'getAttendance']);
    Route::post('/groups/{id}/attendance',                               [GroupController::class, 'takeAttendance']);

    // Assistant teacher management (main teacher only)
    Route::post('/groups/{id}/assistant-teachers',                       [GroupController::class, 'addAssistantTeacher']);
    Route::delete('/groups/{groupId}/assistant-teachers/{teacherId}',    [GroupController::class, 'removeAssistantTeacher']);
});
