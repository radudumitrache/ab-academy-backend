<?php

use App\Http\Controllers\EuPlatescController;
use Illuminate\Support\Facades\Route;

/**
 * EuPlatesc Webhook Routes
 * Prefix: /api/euplatesc
 * No auth middleware — EuPlatesc posts directly to these URLs.
 * These are excluded from CSRF in bootstrap/app.php or VerifyCsrfToken.
 */

Route::post('/notify', [EuPlatescController::class, 'notify']);
Route::post('/return', [EuPlatescController::class, 'return']);
