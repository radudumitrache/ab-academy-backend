<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DatabaseLogController;

/**
 * Admin Database Logs Routes
 * Prefix: /api/admin
 */

Route::get('/logs', [DatabaseLogController::class, 'index'])->middleware('auth:api');
Route::get('/logs/{id}', [DatabaseLogController::class, 'show'])->middleware('auth:api');
