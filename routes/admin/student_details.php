<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StudentDetailController;
use App\Http\Controllers\Admin\UserNotesController;

/**
 * Admin Student Detail Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Student groups endpoint
Route::get('/students/{id}/groups', [StudentDetailController::class, 'getStudentGroups'])
    ->middleware('auth:api');

// Student exams endpoint
Route::get('/students/{id}/exams', [StudentDetailController::class, 'getStudentExams'])
    ->middleware('auth:api');

// Student payments endpoint
Route::get('/students/{id}/payments', [StudentDetailController::class, 'getStudentPayments'])
    ->middleware('auth:api');

// User notes endpoints
Route::get('/users/{id}/notes', [UserNotesController::class, 'getUserNotes'])
    ->middleware('auth:api');
Route::post('/users/{id}/notes', [UserNotesController::class, 'saveUserNote'])
    ->middleware('auth:api');
