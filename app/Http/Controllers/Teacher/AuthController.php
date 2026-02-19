<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            // Check database for teacher
            $user = Teacher::where('username', $request->username)->first();

            // Check if user exists
            if (!$user) {
                Log::warning('Teacher login attempt with non-existent username: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'username' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }
            
            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Teacher login attempt with incorrect password for user: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'password' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            // Generate access token for teacher
            try {
                $token = $user->createToken('teacher-token')->accessToken;
                
                Log::info('Teacher token generated successfully for user: ' . $user->username);
                
                // Return successful response with token
                return response()->json([
                    'message' => 'Teacher login successful',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'role' => $user->role,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate teacher token: ' . $e->getMessage());
                throw $e;
            }
        } catch (ValidationException $e) {
            // Handle validation errors
            Log::warning('Teacher login validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error('Teacher login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $username = $user ? $user->username : 'unknown';
            
            $request->user()->token()->revoke();
            
            Log::info('Teacher logged out successfully: ' . $username);
            
            return response()->json([
                'message' => 'Teacher logout successful',
            ]);
        } catch (\Exception $e) {
            Log::error('Teacher logout error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
