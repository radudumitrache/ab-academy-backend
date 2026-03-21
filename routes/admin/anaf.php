<?php

use App\Http\Controllers\AnafController;
use Illuminate\Support\Facades\Route;

Route::post('/anaf/company-lookup', [AnafController::class, 'lookupCompany']);
