<?php

use App\Http\Controllers\AnafController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::post('/anaf/company-lookup', [AnafController::class, 'lookupCompany']);
});
