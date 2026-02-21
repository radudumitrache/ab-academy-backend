<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;

/*
|--------------------------------------------------------------------------
| Admin Product Routes
|--------------------------------------------------------------------------
|
| Here is where you can register product routes for your application.
|
*/

// Product CRUD operations
Route::get('/products', [ProductController::class, 'index'])->middleware('auth:api');
Route::post('/products', [ProductController::class, 'store'])->middleware('auth:api');
Route::get('/products/{id}', [ProductController::class, 'show'])->middleware('auth:api');
Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('auth:api');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('auth:api');
