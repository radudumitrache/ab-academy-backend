<?php

use App\Http\Controllers\AnafController;
use Illuminate\Support\Facades\Route;

Route::post('/anaf/getCompany', [AnafController::class, 'lookupCompany']);
