<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloController;

/**
 * API Routes
 * 
 * All routes here are automatically prefixed with /api
 * So this route will be accessible at: http://localhost:8000/api/hello
 */

Route::get('/hello', [HelloController::class, 'index']);
