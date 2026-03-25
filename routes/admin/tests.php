<?php

use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\TestQuestionController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Test Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 *
 * Admins can manage all tests regardless of which teacher created them.
 */

// ── Test CRUD ─────────────────────────────────────────────────────────────────
Route::get('/tests',              [TestController::class, 'index']);
Route::post('/tests',             [TestController::class, 'store']);
Route::get('/tests/{id}',         [TestController::class, 'show']);
Route::put('/tests/{id}',         [TestController::class, 'update']);
Route::delete('/tests/{id}',      [TestController::class, 'destroy']);
Route::post('/tests/{id}/assign', [TestController::class, 'assignStudents']);

// ── Sections ──────────────────────────────────────────────────────────────────
Route::get('/tests/{testId}/sections',                [TestController::class, 'sectionIndex']);
Route::post('/tests/{testId}/sections',               [TestController::class, 'sectionStore']);
Route::post('/tests/{testId}/sections/batch',         [TestController::class, 'sectionBatchStore']);
Route::put('/tests/{testId}/sections/{sectionId}',    [TestController::class, 'sectionUpdate']);
Route::delete('/tests/{testId}/sections/{sectionId}', [TestController::class, 'sectionDestroy']);

// ── Questions ─────────────────────────────────────────────────────────────────
Route::post('/tests/{testId}/questions',                [TestQuestionController::class, 'store']);
Route::put('/tests/{testId}/questions/{questionId}',    [TestQuestionController::class, 'update']);
Route::delete('/tests/{testId}/questions/{questionId}', [TestQuestionController::class, 'destroy']);

// ── Submissions (read-only) ───────────────────────────────────────────────────
Route::get('/tests/{id}/submissions', [TestController::class, 'submissions']);
