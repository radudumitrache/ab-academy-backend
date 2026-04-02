<?php

use App\Http\Controllers\Admin\MeetingAccountController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Meeting Account Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::get('/meeting-accounts',              [MeetingAccountController::class, 'index']);
Route::post('/meeting-accounts',             [MeetingAccountController::class, 'store']);
Route::get('/meeting-accounts/{id}',         [MeetingAccountController::class, 'show']);
Route::put('/meeting-accounts/{id}',         [MeetingAccountController::class, 'update']);
Route::delete('/meeting-accounts/{id}',      [MeetingAccountController::class, 'destroy']);
Route::post('/meeting-accounts/{id}/test',          [MeetingAccountController::class, 'test']);
Route::get('/meeting-accounts/{id}/check-meetings', [MeetingAccountController::class, 'checkMeetings']);
