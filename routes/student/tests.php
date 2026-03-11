<?php

use App\Http\Controllers\Student\TestController;
use Illuminate\Support\Facades\Route;

/**
 * Student Test Routes
 * Prefix: /api/student
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/tests',                    [TestController::class, 'index']);
    Route::get('/tests/{id}',               [TestController::class, 'show']);
    Route::get('/tests/{id}/results',       [TestController::class, 'results']);
    Route::post('/tests/{id}/answers',      [TestController::class, 'saveAnswers']);
    Route::post('/tests/{id}/submit',       [TestController::class, 'submit']);
});
