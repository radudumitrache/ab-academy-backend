<?php

use App\Http\Controllers\Teacher\HomeworkController;
use App\Http\Controllers\Teacher\HomeworkSubmissionController;
use App\Http\Controllers\Teacher\QuestionController;
use App\Http\Controllers\Teacher\SectionController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Homework Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 *
 * Flow:
 *   1. Create homework       POST   /homework
 *   2. Assign students       POST   /homework/{id}/assign
 *   3. Create sections       POST   /homework/{id}/sections
 *   4. Add questions         POST   /homework/{id}/questions  (section_id required)
 */

Route::middleware('auth:api')->group(function () {

    // ── Homework CRUD ─────────────────────────────────────────────────────────
    Route::get('/homework',              [HomeworkController::class, 'index']);
    Route::post('/homework',             [HomeworkController::class, 'store']);
    Route::get('/homework/{id}',         [HomeworkController::class, 'show']);
    Route::put('/homework/{id}',         [HomeworkController::class, 'update']);
    Route::delete('/homework/{id}',      [HomeworkController::class, 'destroy']);
    Route::post('/homework/{id}/assign', [HomeworkController::class, 'assignStudents']);

    // ── Submissions ───────────────────────────────────────────────────────────
    Route::get('/homework/{homeworkId}/submissions',                                       [HomeworkSubmissionController::class, 'index']);
    Route::get('/homework/{homeworkId}/submissions/{submissionId}',                        [HomeworkSubmissionController::class, 'show']);
    Route::patch('/homework/{homeworkId}/submissions/{submissionId}/grade',                [HomeworkSubmissionController::class, 'grade']);
    Route::patch('/homework/{homeworkId}/submissions/{submissionId}/grade-responses',      [HomeworkSubmissionController::class, 'gradeResponses']);

    // ── Sections CRUD ─────────────────────────────────────────────────────────
    Route::get('/homework/{homeworkId}/sections',               [SectionController::class, 'index']);
    Route::post('/homework/{homeworkId}/sections',              [SectionController::class, 'store']);
    Route::put('/homework/{homeworkId}/sections/{sectionId}',   [SectionController::class, 'update']);
    Route::delete('/homework/{homeworkId}/sections/{sectionId}',[SectionController::class, 'destroy']);

    // ── Questions CRUD ────────────────────────────────────────────────────────
    Route::post('/homework/{homeworkId}/questions',                [QuestionController::class, 'store']);
    Route::put('/homework/{homeworkId}/questions/{questionId}',    [QuestionController::class, 'update']);
    Route::delete('/homework/{homeworkId}/questions/{questionId}', [QuestionController::class, 'destroy']);
});
