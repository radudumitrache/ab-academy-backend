<?php

use App\Http\Controllers\AnafController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::post('/anaf/get-company', [AnafController::class, 'lookupCompany']);
});
