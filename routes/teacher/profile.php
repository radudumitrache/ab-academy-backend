<?php

use App\Http\Controllers\Teacher\ProfileController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Profile Routes
 * Prefix: /api/teacher
 * Middleware: auth:api
 */

Route::middleware('auth:api')->group(function () {
    Route::post('/profile/setup',           [ProfileController::class, 'setupStorage']);
    Route::get('/profile',                  [ProfileController::class, 'show']);
    Route::put('/profile',                  [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/picture',         [ProfileController::class, 'uploadProfilePicture']);
    Route::get('/profile/picture',          [ProfileController::class, 'getProfilePicture']);
});
