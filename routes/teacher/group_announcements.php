<?php

use App\Http\Controllers\Teacher\GroupAnnouncementController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher Group Announcement Routes
 * Prefix: /api/teacher
 * Middleware: auth:api (applied per group below)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/group-announcements',         [GroupAnnouncementController::class, 'index']);
    Route::post('/group-announcements',        [GroupAnnouncementController::class, 'store']);
    Route::get('/group-announcements/{id}',    [GroupAnnouncementController::class, 'show']);
    Route::put('/group-announcements/{id}',    [GroupAnnouncementController::class, 'update']);
    Route::delete('/group-announcements/{id}', [GroupAnnouncementController::class, 'destroy']);
});
