<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NotificationController;

// Mark all seen must come before {id} routes to avoid route conflict
Route::put('/notifications/seen-all', [NotificationController::class, 'markAllSeen']);

Route::get('/notifications',          [NotificationController::class, 'index']);
Route::post('/notifications',         [NotificationController::class, 'store']);
Route::put('/notifications/{id}/seen',[NotificationController::class, 'markSeen']);
Route::delete('/notifications/{id}',  [NotificationController::class, 'destroy']);
