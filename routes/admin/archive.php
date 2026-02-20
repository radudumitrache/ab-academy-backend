<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ArchiveController;

/**
 * Admin Archive Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Archived courses
Route::get('/archived/courses', [ArchiveController::class, 'archivedCourses'])->middleware('auth:api');
Route::put('/archived/courses/{id}/restore', [ArchiveController::class, 'restoreCourse'])->middleware('auth:api');

// Archived groups
Route::get('/archived/groups', [ArchiveController::class, 'archivedGroups'])->middleware('auth:api');
Route::put('/archived/groups/{id}/restore', [ArchiveController::class, 'restoreGroup'])->middleware('auth:api');
