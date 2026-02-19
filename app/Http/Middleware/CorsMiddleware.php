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
        $response = $next($request);

        // Remove any existing CORS headers to prevent duplicates
        foreach (['Access-Control-Allow-Origin', 'Access-Control-Allow-Methods', 
                 'Access-Control-Allow-Headers', 'Access-Control-Allow-Credentials'] as $header) {
            $response->headers->remove($header);
        }
        
        // Set CORS headers
        $response->header('Access-Control-Allow-Origin', 'http://127.0.0.1:8081');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->header('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}
