<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SimpleCors
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
        // For preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
            $this->addCorsHeaders($request, $response);
            return $response;
        }
        
        // For normal requests
        $response = $next($request);
        $this->addCorsHeaders($request, $response);
        return $response;
    }
    
    /**
     * Add CORS headers to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    protected function addCorsHeaders(Request $request, $response)
    {
        // Remove any existing CORS headers to prevent duplicates
        foreach (['Access-Control-Allow-Origin', 'Access-Control-Allow-Methods', 
                 'Access-Control-Allow-Headers', 'Access-Control-Allow-Credentials'] as $header) {
            $response->headers->remove($header);
        }
        
        // Set the specific origin — check request origin against allowed list
        $allowedOrigins = config('cors.allowed_origins', []);
        $requestOrigin  = $request->headers->get('Origin', '');
        $origin = in_array($requestOrigin, $allowedOrigins) ? $requestOrigin : ($allowedOrigins[0] ?? '*');
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
    }
}
