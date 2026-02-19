<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

/**
 * Admin Dashboard Routes
 * Prefix: /api/admin
 */

Route::middleware('auth:api')->group(function () {
    // Main dashboard endpoint
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Dashboard KPI endpoint
    Route::get('/dashboard/kpi', [DashboardController::class, 'getKpi']);
    
    // Dashboard activities endpoint
    Route::get('/dashboard/activities', [DashboardController::class, 'getActivities']);
});
