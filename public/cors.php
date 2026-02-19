<?php
// Simple CORS handler - sets headers once at the beginning of the request

// For preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Set the specific origin from the request
    if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === 'http://127.0.0.1:8081') {
        header('Access-Control-Allow-Origin: http://127.0.0.1:8081');
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    
    // Stop processing - just return 200 OK for preflight
    http_response_code(200);
    exit;
}

// For actual requests (non-OPTIONS)
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === 'http://127.0.0.1:8081') {
    header('Access-Control-Allow-Origin: http://127.0.0.1:8081');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Credentials: true');
