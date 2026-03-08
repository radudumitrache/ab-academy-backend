<?php

use App\Http\Controllers\Student\ExamController;
use Illuminate\Support\Facades\Route;

/**
 * Student Exam Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    // Admin-enrolled exams (read-only)
    Route::get('/exams',      [ExamController::class, 'index']);
    Route::get('/exams/{id}', [ExamController::class, 'show']);

    // Personal exams (student-managed)
    Route::get('/personal-exams',           [ExamController::class, 'personalIndex']);
    Route::post('/personal-exams',          [ExamController::class, 'personalStore']);
    Route::get('/personal-exams/{id}',      [ExamController::class, 'personalShow']);
    Route::put('/personal-exams/{id}',      [ExamController::class, 'personalUpdate']);
    Route::delete('/personal-exams/{id}',   [ExamController::class, 'personalDestroy']);
});
