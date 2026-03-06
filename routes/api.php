<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloController;
use Illuminate\Http\Request;

/**
 * API Routes
 * 
 * All routes here are automatically prefixed with /api
 * So this route will be accessible at: http://localhost:8000/api/hello
 */

Route::get('/hello', [HelloController::class, 'index']);

Route::post('/debug-upload', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'has_file'    => $request->hasFile('file'),
        'all_input'   => $request->all(),
        'files'       => array_keys($request->allFiles()),
        'content_type'=> $request->header('Content-Type'),
    ]);
});

Route::get('/phpinfo-upload', function () {
    return response()->json([
        'file_uploads'        => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size'       => ini_get('post_max_size'),
    ]);
});

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
