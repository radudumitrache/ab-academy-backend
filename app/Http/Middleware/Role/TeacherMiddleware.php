<?php

namespace App\Http\Middleware\Role;

use Closure;
use Illuminate\Http\Request;

class TeacherMiddleware
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
        $user = $request->user();

        if (!$user || $user->role !== 'teacher') {
            return response()->json([
                'message' => 'Access denied. Teacher role required.',
            ], 403);
        }

        return $next($request);
    }
}
