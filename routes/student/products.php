<?php

use App\Http\Controllers\Student\ProductController;
use App\Http\Controllers\Student\ProductAcquisitionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    // Browse products
    Route::get('/products',       [ProductController::class, 'index']);
    Route::get('/products/{id}',  [ProductController::class, 'show']);

    // Acquisitions
    Route::get('/acquisitions',                      [ProductAcquisitionController::class, 'index']);
    Route::get('/acquisitions/{id}',                 [ProductAcquisitionController::class, 'show']);
    Route::post('/products/{id}/create_checkout',    [ProductAcquisitionController::class, 'purchase']);
    Route::post('/acquisitions/{id}/renew',          [ProductAcquisitionController::class, 'renew']);
});
