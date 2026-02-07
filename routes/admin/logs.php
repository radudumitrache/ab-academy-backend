<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DatabaseLogController;

/**
 * Admin Database Logs Routes
 * Prefix: /api/admin
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/logs', [DatabaseLogController::class, 'index']);
    Route::get('/logs/{id}', [DatabaseLogController::class, 'show']);
});
