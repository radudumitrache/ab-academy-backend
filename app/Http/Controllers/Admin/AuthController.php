<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Simplified login method for testing
        try {
            // Return a basic response to test if the controller method is being called
            return response()->json([
                'message' => 'AuthController login method reached successfully',
                'request_data' => $request->all()
            ]);
            
            /* Original code commented out for testing
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = Admin::where('username', $request->username)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('admin-token')->accessToken;

            return response()->json([
                'message' => 'Admin login successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
            */
        } catch (\Exception $e) {
            // Catch any exceptions and return them as a response for debugging
            return response()->json([
                'error' => 'Exception in login method',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Admin logout successful',
        ]);
    }
}
