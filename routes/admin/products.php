<?php

use App\Http\Controllers\Admin\EuPlatescTransactionController;
use App\Http\Controllers\Admin\PaymentProfileController;
use App\Http\Controllers\Admin\ProductAcquisitionController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

// ── Product management ────────────────────────────────────────────────────────
Route::get('/products',          [ProductController::class, 'index']);
Route::get('/products/{id}',     [ProductController::class, 'show']);
Route::post('/products',         [ProductController::class, 'store']);
Route::put('/products/{id}',     [ProductController::class, 'update']);
Route::delete('/products/{id}',  [ProductController::class, 'destroy']);

// ── Acquisition management ────────────────────────────────────────────────────
Route::get('/acquisitions',                                [ProductAcquisitionController::class, 'index']);
Route::post('/acquisitions',                               [ProductAcquisitionController::class, 'store']);
Route::get('/acquisitions/{id}',                           [ProductAcquisitionController::class, 'show']);
Route::post('/acquisitions/{id}/grant-access',             [ProductAcquisitionController::class, 'grantAccess']);
Route::post('/acquisitions/create-invoice',                [ProductAcquisitionController::class, 'createInvoice']);
Route::get('/acquisitions/{id}/download-invoice',          [ProductAcquisitionController::class, 'downloadInvoice']);
Route::post('/acquisitions/{id}/mark-invoice-paid',        [ProductAcquisitionController::class, 'markInvoicePaid']);
Route::post('/acquisitions/{id}/send-invoice-email',       [ProductAcquisitionController::class, 'sendInvoiceByEmail']);
Route::put('/acquisitions/{id}/status',                    [ProductAcquisitionController::class, 'updateStatus']);

// ── Payment profiles (admin view) ─────────────────────────────────────────────
Route::get('/payment-profiles',                            [PaymentProfileController::class, 'index']);
Route::get('/payment-profiles/{id}',                       [PaymentProfileController::class, 'show']);
Route::get('/students/{studentId}/payment-profiles',       [PaymentProfileController::class, 'forStudent']);
Route::post('/payment-profiles/{id}/set-invoice-text',     [PaymentProfileController::class, 'setInvoiceText']);
Route::post('/payment-profiles/{id}/confirm',              [PaymentProfileController::class, 'confirm']);

// ── EuPlatesc transactions ────────────────────────────────────────────────────
Route::get('/euplatesc-transactions',                      [EuPlatescTransactionController::class, 'index']);
Route::get('/euplatesc-transactions/{id}/check-status',    [EuPlatescTransactionController::class, 'checkStatus']);
