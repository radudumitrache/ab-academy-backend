<?php

use App\Http\Controllers\Student\GroupAnnouncementController;
use Illuminate\Support\Facades\Route;

/**
 * Student Group Announcement Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied per group below)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/groups/{groupId}/announcements', [GroupAnnouncementController::class, 'getGroupAnnouncements']);
});
