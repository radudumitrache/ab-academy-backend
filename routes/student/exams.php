<?php

use App\Http\Controllers\Student\ExamController;
use Illuminate\Support\Facades\Route;

/**
 * Student Exam Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/exams',              [ExamController::class, 'index']);
    Route::get('/exams/available',    [ExamController::class, 'available']);
    Route::post('/exams',             [ExamController::class, 'store']);
    Route::get('/exams/{id}',         [ExamController::class, 'show']);
    Route::patch('/exams/{id}/score', [ExamController::class, 'updateScore']);
    Route::delete('/exams/{id}',      [ExamController::class, 'destroy']);
});
