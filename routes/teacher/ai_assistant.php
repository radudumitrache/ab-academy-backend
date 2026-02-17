<?php

use App\Http\Controllers\Teacher\AiAssistantController;
use Illuminate\Support\Facades\Route;

/**
 * Teacher AI Assistant Routes
 * Prefix: /api/teacher
 */

Route::post('/ai-assistant/translate', [AiAssistantController::class, 'translate'])->middleware('auth:api');