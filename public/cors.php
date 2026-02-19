<?php
// This file handles CORS for all requests
// It should be included at the top of index.php

// Only set headers if they haven't been set already
if (!function_exists('header_sent')) {
    function header_sent($header_name) {
        foreach (headers_list() as $header) {
            if (strpos(strtolower($header), strtolower($header_name) . ':') === 0) {
                return true;
            }
        }
        return false;
    }
}

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (!header_sent('Access-Control-Allow-Origin')) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    }
    if (!header_sent('Access-Control-Allow-Credentials')) {
        header('Access-Control-Allow-Credentials: true');
    }
    if (!header_sent('Access-Control-Max-Age')) {
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && !header_sent('Access-Control-Allow-Methods')) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
    }
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) && !header_sent('Access-Control-Allow-Headers')) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    
    // Exit early so the page isn't fully loaded for OPTIONS requests
    exit(0);
}
