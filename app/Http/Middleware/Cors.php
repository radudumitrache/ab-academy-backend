<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight OPTIONS requests immediately
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Get origin
        $origin = $request->header('Origin');
        
        // Set allowed origins
        $allowed_origins = ['http://127.0.0.1:8081', 'http://localhost:8081'];
        
        // If origin is in allowed list or we're allowing all origins
        if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        } else {
            $response->header('Access-Control-Allow-Origin', '*');
        }
        
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', '86400'); // 24 hours
        
        return $response;
    }
}
