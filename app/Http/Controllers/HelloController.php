<?php

namespace App\Http\Controllers;

class HelloController
{
    /**
     * Return a simple "Hello World" message
     * 
     * This method handles GET requests to /api/hello
     * and returns a JSON response with a greeting message
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'message' => 'Hello World!'
        ]);
    }
}
