<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\HelloController;
use Illuminate\Http\Request;

/**
 * API Routes
 * 
 * All routes here are automatically prefixed with /api
 * So this route will be accessible at: http://localhost:8000/api/hello
 */

Route::get('/hello', [HelloController::class, 'index']);

/**
 * Connectivity Diagnostics Route
 *
 * Reports what the server actually received from the client: whether the
 * Authorization header survived the trip, and whether any proxy or filtering
 * middlebox sits between the device and the server. Public by design — it
 * diagnoses devices that cannot authenticate at all. Never echoes token or
 * cookie values.
 */
Route::match(['get', 'post'], '/diagnostics', [DiagnosticsController::class, 'index']);
Route::get('/diagnostics/view', [DiagnosticsController::class, 'page']);

/**
 * API Documentation Route
 * 
 * Provides information about available authentication endpoints
 */
Route::get('/auth-info', function() {
    return response()->json([
        'message' => 'Authentication endpoints information',
        'endpoints' => [
            [
                'role' => 'admin',
                'login_url' => '/api/admin/login',
                'method' => 'POST',
                'required_fields' => ['username', 'password'],
                'description' => 'Login endpoint for administrators',
            ],
            [
                'role' => 'teacher',
                'login_url' => '/api/teacher/login',
                'method' => 'POST',
                'required_fields' => ['username', 'password'],
                'description' => 'Login endpoint for teachers',
            ],
            [
                'role' => 'student',
                'login_url' => '/api/student/login',
                'method' => 'POST',
                'required_fields' => ['username', 'password'],
                'description' => 'Login endpoint for students',
            ],
        ],
        'token_usage' => [
            'header' => 'Authorization: Bearer YOUR_TOKEN',
            'example' => 'curl -H "Authorization: Bearer eyJ0eXAiOi..." https://backend.andreeaberkhout.com/api/admin/dashboard',
        ],
    ]);
});

/**
 * CORS Test Route
 * 
 * Used to verify CORS configuration is working properly
 */
Route::options('/cors-test', function() {
    return response()->json(['message' => 'CORS preflight request successful']);
});

Route::get('/cors-test', function() {
    return response()->json([
        'message' => 'CORS is working properly',
        'headers' => collect(request()->headers->all())
            ->map(function($item) {
                return is_array($item) ? implode(', ', $item) : $item;
            })
            ->toArray(),
        'cors_headers' => [
            'origin' => request()->header('Origin'),
            'access_control_request_method' => request()->header('Access-Control-Request-Method'),
        ],
    ]);
});
