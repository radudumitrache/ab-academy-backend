<?php

use App\Http\Controllers\AnafController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::match(['get', 'post'], '/company', [AnafController::class, 'lookupCompany']);
});
