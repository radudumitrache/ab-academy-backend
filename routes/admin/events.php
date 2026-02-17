<?php

use App\Http\Controllers\Events\EventController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Event Management Routes
 * Prefix: /api/admin
 */

Route::get('/events', [EventController::class, 'index'])->middleware('auth:api');
Route::post('/events', [EventController::class, 'store'])->middleware('auth:api');
Route::get('/events/{id}', [EventController::class, 'show'])->middleware('auth:api');
Route::put('/events/{id}', [EventController::class, 'update'])->middleware('auth:api');
Route::delete('/events/{id}', [EventController::class, 'destroy'])->middleware('auth:api');
