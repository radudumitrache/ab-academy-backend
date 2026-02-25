<?php

use App\Http\Controllers\Teacher\HomeworkController;
use App\Http\Controllers\Teacher\QuestionController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Homework Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {

    // ── Homework CRUD ─────────────────────────────────────────────────────────
    Route::get('/homework',          [HomeworkController::class, 'index']);
    Route::post('/homework',         [HomeworkController::class, 'store']);
    Route::get('/homework/{id}',     [HomeworkController::class, 'show']);
    Route::put('/homework/{id}',     [HomeworkController::class, 'update']);
    Route::delete('/homework/{id}',  [HomeworkController::class, 'destroy']);
    Route::post('/homework/{id}/assign', [HomeworkController::class, 'assignStudents']);

    // ── Questions CRUD ────────────────────────────────────────────────────────
    Route::post('/homework/{homeworkId}/questions',               [QuestionController::class, 'store']);
    Route::put('/homework/{homeworkId}/questions/{questionId}',   [QuestionController::class, 'update']);
    Route::delete('/homework/{homeworkId}/questions/{questionId}',[QuestionController::class, 'destroy']);

    // ── Sections (Reading / Listening) ────────────────────────────────────────
    Route::post('/homework/{homeworkId}/sections',              [QuestionController::class, 'storeSection']);
    Route::delete('/homework/{homeworkId}/sections/{sectionId}',[QuestionController::class, 'destroySection']);
});
