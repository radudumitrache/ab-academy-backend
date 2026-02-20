<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ExamController;

/**
 * Admin Exam Management Routes
 * Prefix: /api/admin
 * Middleware: auth:api
 */

// Exam CRUD operations
Route::get('/exams', [ExamController::class, 'index'])->middleware('auth:api');
Route::post('/exams', [ExamController::class, 'store'])->middleware('auth:api');
Route::get('/exams/{id}', [ExamController::class, 'show'])->middleware('auth:api');
Route::put('/exams/{id}', [ExamController::class, 'update'])->middleware('auth:api');
Route::delete('/exams/{id}', [ExamController::class, 'destroy'])->middleware('auth:api');

// Exam student enrollment
Route::post('/exams/{id}/students', [ExamController::class, 'enrollStudents'])->middleware('auth:api');
Route::delete('/exams/{examId}/students/{studentId}', [ExamController::class, 'removeStudent'])->middleware('auth:api');
