<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StudentPurchaseController;

/*
|--------------------------------------------------------------------------
| Admin Student Purchase Routes
|--------------------------------------------------------------------------
|
| Here is where you can register student purchase routes for your application.
|
*/

// Student purchase operations
Route::get('/students/{id}/purchases', [StudentPurchaseController::class, 'getStudentPurchases'])->middleware('auth:api');
Route::post('/students/{id}/purchases', [StudentPurchaseController::class, 'recordPurchase'])->middleware('auth:api');
Route::delete('/students/{studentId}/purchases/{productId}', [StudentPurchaseController::class, 'removePurchase'])->middleware('auth:api');
