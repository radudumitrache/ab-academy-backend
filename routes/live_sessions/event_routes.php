<?php

use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\EventInviteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('events')->group(function () {
    // Event CRUD operations
    Route::post('/create-event', [EventController::class, 'store']);
    Route::put('/edit-event/{id}', [EventController::class, 'update']);
    Route::get('/view-event/{id}', [EventController::class, 'show']);
    
    // Event invitations
    Route::post('/invite-people/{id}', [EventInviteController::class, 'invite']);
});