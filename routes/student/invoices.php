<?php

use App\Http\Controllers\Student\InvoiceController;
use Illuminate\Support\Facades\Route;

/**
 * Student Invoice Routes
 * Prefix: /api/student
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::middleware('auth:api')->group(function () {
    Route::get('/invoices',          [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}',     [InvoiceController::class, 'show']);
    Route::post('/invoices/{id}/pay',[InvoiceController::class, 'pay']);
});
