<?php

use App\Http\Controllers\Student\PaymentProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/payment-profiles',          [PaymentProfileController::class, 'index']);
    Route::get('/payment-profiles/{id}',     [PaymentProfileController::class, 'show']);
    Route::post('/payment-profiles',         [PaymentProfileController::class, 'store']);
    Route::put('/payment-profiles/{id}',     [PaymentProfileController::class, 'update']);
    Route::delete('/payment-profiles/{id}',  [PaymentProfileController::class, 'destroy']);
});
