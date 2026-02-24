<?php
// Simple CORS handler - sets headers once at the beginning of the request

$allowedOrigins = array_filter(explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'https://admin.andreeaberkhout.com'));
$requestOrigin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$origin = in_array($requestOrigin, $allowedOrigins) ? $requestOrigin : '*';

// For preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    http_response_code(200);
    exit;
}

// For actual requests (non-OPTIONS)
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
