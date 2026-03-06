<?php

use App\Http\Controllers\Admin\MaterialController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Material Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::get('/materials',              [MaterialController::class, 'index']);
Route::post('/materials/upload',      [MaterialController::class, 'upload']);
Route::get('/materials/{id}',         [MaterialController::class, 'show']);
Route::put('/materials/{id}/access',  [MaterialController::class, 'updateAccess']);
Route::delete('/materials/{id}',      [MaterialController::class, 'destroy']);
