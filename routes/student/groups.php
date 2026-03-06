<?php

use App\Http\Controllers\Student\GroupController;
use Illuminate\Support\Facades\Route;

/**
 * Student Group Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::post('/groups/join',    [GroupController::class, 'joinByCode']);
    Route::get('/groups/hours',    [GroupController::class, 'courseHours']);
});
