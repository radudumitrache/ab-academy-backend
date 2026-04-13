<?php

use App\Http\Controllers\Admin\MaterialController;
use Illuminate\Support\Facades\Route;

/**
 * Admin Material Routes
 * Prefix: /api/admin
 * Middleware: auth:api (applied in RouteServiceProvider)
 */

Route::get('/materials',              [MaterialController::class, 'index']);
Route::post('/materials/upload',      [MaterialController::class, 'upload']);
Route::get('/materials/{id}',         [MaterialController::class, 'show']);
Route::put('/materials/{id}/access',  [MaterialController::class, 'updateAccess']);
Route::delete('/materials/{id}',      [MaterialController::class, 'destroy']);

// Storage folder management — operates on any bucket path
Route::get('/storage/list',           [MaterialController::class, 'listObjects']);
Route::get('/storage/folders',        [MaterialController::class, 'listFolders']);
Route::post('/storage/folders',       [MaterialController::class, 'createFolder']);
Route::delete('/storage/folders',     [MaterialController::class, 'deleteFolder']);

// Storage repair — creates missing Material DB records from GCS objects
Route::post('/storage/repair',        [MaterialController::class, 'repairMaterials']);
