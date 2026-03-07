<?php

use App\Http\Controllers\Teacher\TestController;
use App\Http\Controllers\Teacher\TestQuestionController;
use App\Http\Controllers\Teacher\TestSectionController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Test Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 *
 * Flow:
 *   1. Create test          POST   /tests
 *   2. Assign students      POST   /tests/{id}/assign
 *   3. Create sections      POST   /tests/{id}/sections
 *   4. Add questions        POST   /tests/{id}/questions  (section_id required)
 */

Route::middleware('auth:api')->group(function () {

    // ── Test CRUD ─────────────────────────────────────────────────────────────
    Route::get('/tests',              [TestController::class, 'index']);
    Route::post('/tests',             [TestController::class, 'store']);
    Route::get('/tests/{id}',         [TestController::class, 'show']);
    Route::put('/tests/{id}',         [TestController::class, 'update']);
    Route::delete('/tests/{id}',      [TestController::class, 'destroy']);
    Route::post('/tests/{id}/assign', [TestController::class, 'assignStudents']);

    // ── Sections CRUD ─────────────────────────────────────────────────────────
    Route::get('/tests/{testId}/sections',                 [TestSectionController::class, 'index']);
    Route::post('/tests/{testId}/sections',                [TestSectionController::class, 'store']);
    Route::put('/tests/{testId}/sections/{sectionId}',     [TestSectionController::class, 'update']);
    Route::delete('/tests/{testId}/sections/{sectionId}',  [TestSectionController::class, 'destroy']);

    // ── Questions CRUD ────────────────────────────────────────────────────────
    Route::post('/tests/{testId}/questions',                [TestQuestionController::class, 'store']);
    Route::put('/tests/{testId}/questions/{questionId}',    [TestQuestionController::class, 'update']);
    Route::delete('/tests/{testId}/questions/{questionId}', [TestQuestionController::class, 'destroy']);
});
