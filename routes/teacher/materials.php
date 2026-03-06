<?php

use App\Http\Controllers\Teacher\MaterialController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Material Routes
 * Prefix: /api/teacher
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::post('/materials/setup',       [MaterialController::class, 'setupStorage']);
    Route::get('/materials',              [MaterialController::class, 'index']);
    Route::post('/materials/upload',      [MaterialController::class, 'upload']);
    Route::get('/materials/{id}',         [MaterialController::class, 'show']);
    Route::put('/materials/{id}/access',  [MaterialController::class, 'updateAccess']);
    Route::delete('/materials/{id}',      [MaterialController::class, 'destroy']);
});
