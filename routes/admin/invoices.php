<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;

/*
|--------------------------------------------------------------------------
| Admin Invoice Routes
|--------------------------------------------------------------------------
|
| Here is where you can register invoice routes for your application.
|
*/

// Invoice CRUD operations
Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('auth:api');
Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('auth:api');
Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->middleware('auth:api');
Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->middleware('auth:api');
Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->middleware('auth:api');

// Invoice status update
Route::put('/invoices/{id}/status', [InvoiceController::class, 'updateStatus'])->middleware('auth:api');

// Student invoices
Route::get('/students/{id}/invoices', [InvoiceController::class, 'getStudentInvoices'])->middleware('auth:api');
