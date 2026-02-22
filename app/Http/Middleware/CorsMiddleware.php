<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
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
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Remove any existing CORS headers to prevent duplicates
        foreach (['Access-Control-Allow-Origin', 'Access-Control-Allow-Methods', 
                 'Access-Control-Allow-Headers', 'Access-Control-Allow-Credentials'] as $header) {
            $response->headers->remove($header);
        }
        
        // Get the origin
        $origin = $request->header('Origin');
        
        // Set CORS headers
        // Allow the specific origin or use a wildcard
        $response->header('Access-Control-Allow-Origin', $origin ?: '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', '86400'); // 24 hours
        
        return $response;
    }
}
