<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
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

            // Check database for student
            $user = Student::where('username', $request->username)->first();

            // Check if user exists
            if (!$user) {
                Log::warning('Student login attempt with non-existent username: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'username' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }
            
            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Student login attempt with incorrect password for user: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'password' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            // Generate access token for student
            try {
                $token = $user->createToken('student-token')->accessToken;
                
                Log::info('Student token generated successfully for user: ' . $user->username);
                
                // Return successful response with token
                return response()->json([
                    'message' => 'Student login successful',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'role' => $user->role,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate student token: ' . $e->getMessage());
                throw $e;
            }
        } catch (ValidationException $e) {
            // Handle validation errors
            Log::warning('Student login validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error('Student login error: ' . $e->getMessage());
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
            
            Log::info('Student logged out successfully: ' . $username);
            
            return response()->json([
                'message' => 'Student logout successful',
            ]);
        } catch (\Exception $e) {
            Log::error('Student logout error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
