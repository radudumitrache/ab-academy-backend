<?php

use App\Http\Controllers\Student\HomeworkController;
use Illuminate\Support\Facades\Route;

/**
 * Student Homework Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/homework',                     [HomeworkController::class, 'index']);
    Route::get('/homework/{id}',                [HomeworkController::class, 'show']);
    Route::get('/homework/{id}/results',        [HomeworkController::class, 'results']);
    Route::post('/homework/{id}/answers',       [HomeworkController::class, 'saveAnswers']);
    Route::post('/homework/{id}/submit',        [HomeworkController::class, 'submit']);
});
