<?php

use App\Http\Controllers\Student\MaterialController;
use Illuminate\Support\Facades\Route;

/**
 * Student Material Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/materials',      [MaterialController::class, 'index']);
    Route::get('/materials/{id}', [MaterialController::class, 'show']);
});
