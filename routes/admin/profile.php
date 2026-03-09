<?php

use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Profile Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::post('/profile/setup',           [ProfileController::class, 'setupStorage']);
Route::get('/profile',                  [ProfileController::class, 'show']);
Route::put('/profile',                  [ProfileController::class, 'update']);
Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
Route::post('/profile/picture',         [ProfileController::class, 'uploadProfilePicture']);
Route::get('/profile/picture',          [ProfileController::class, 'getProfilePicture']);
