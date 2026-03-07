<?php

use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\HomeworkQuestionController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Homework Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 *
 * Admins can manage all homework regardless of which teacher created it.
 */

// ── Homework CRUD ─────────────────────────────────────────────────────────────
Route::get('/homework',              [HomeworkController::class, 'index']);
Route::post('/homework',             [HomeworkController::class, 'store']);
Route::get('/homework/{id}',         [HomeworkController::class, 'show']);
Route::put('/homework/{id}',         [HomeworkController::class, 'update']);
Route::delete('/homework/{id}',      [HomeworkController::class, 'destroy']);
Route::post('/homework/{id}/assign', [HomeworkController::class, 'assignStudents']);

// ── Sections ──────────────────────────────────────────────────────────────────
Route::get('/homework/{homeworkId}/sections',                [HomeworkController::class, 'sectionIndex']);
Route::post('/homework/{homeworkId}/sections',               [HomeworkController::class, 'sectionStore']);
Route::put('/homework/{homeworkId}/sections/{sectionId}',    [HomeworkController::class, 'sectionUpdate']);
Route::delete('/homework/{homeworkId}/sections/{sectionId}', [HomeworkController::class, 'sectionDestroy']);

// ── Questions ─────────────────────────────────────────────────────────────────
Route::post('/homework/{homeworkId}/questions',                [HomeworkQuestionController::class, 'store']);
Route::put('/homework/{homeworkId}/questions/{questionId}',    [HomeworkQuestionController::class, 'update']);
Route::delete('/homework/{homeworkId}/questions/{questionId}', [HomeworkQuestionController::class, 'destroy']);

// ── Submissions (read-only) ───────────────────────────────────────────────────
Route::get('/homework/{id}/submissions', [HomeworkController::class, 'submissions']);
