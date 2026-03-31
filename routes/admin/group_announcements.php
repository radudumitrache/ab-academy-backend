<?php

use App\Http\Controllers\Admin\GroupAnnouncementController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Group Announcement Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::get('/group-announcements',       [GroupAnnouncementController::class, 'index']);
Route::post('/group-announcements',      [GroupAnnouncementController::class, 'store']);
Route::get('/group-announcements/{id}',  [GroupAnnouncementController::class, 'show']);
Route::put('/group-announcements/{id}',  [GroupAnnouncementController::class, 'update']);
Route::delete('/group-announcements/{id}', [GroupAnnouncementController::class, 'destroy']);
