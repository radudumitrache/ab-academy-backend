<?php

use App\Http\Controllers\Teacher\ExamController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Exam Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/exams',                                         [ExamController::class, 'index']);
    Route::post('/exams',                                        [ExamController::class, 'store']);
    Route::get('/exams/{id}',                                    [ExamController::class, 'show']);
    Route::post('/exams/{id}/students',                          [ExamController::class, 'enrollStudents']);
    Route::delete('/exams/{examId}/students/{studentId}',        [ExamController::class, 'removeStudent']);
});
