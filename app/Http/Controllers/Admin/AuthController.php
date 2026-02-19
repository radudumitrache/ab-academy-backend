<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
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

            // Check for super admin credentials from .env file
            $superAdminUsername = env('SUPER_ADMIN_USERNAME');
            $superAdminPassword = env('SUPER_ADMIN_PASSWORD');
            
            // If super admin credentials are set and match the request
            if ($superAdminUsername && $superAdminPassword && 
                $request->username === $superAdminUsername && 
                $request->password === $superAdminPassword) {
                
                // Create a temporary admin user object for token generation
                $superAdmin = new Admin();
                $superAdmin->id = 0; // Special ID for super admin
                $superAdmin->username = $superAdminUsername;
                $superAdmin->role = 'super_admin';
                
                // Generate access token for super admin
                try {
                    $token = $superAdmin->createToken('super-admin-token')->accessToken;
                    
                    Log::info('Super admin token generated successfully');
                    
                    return response()->json([
                        'message' => 'Super admin login successful',
                        'user' => [
                            'id' => 0,
                            'username' => $superAdminUsername,
                            'role' => 'super_admin',
                        ],
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate super admin token: ' . $e->getMessage());
                    throw $e;
                }
            }
            
            // If not super admin, check database for regular admin
            $user = Admin::where('username', $request->username)->first();

            // Check if user exists and password is correct
            if (!$user) {
                Log::warning('Admin login attempt with non-existent username: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'username' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }
            
            if (!Hash::check($request->password, $user->password)) {
                Log::warning('Admin login attempt with incorrect password for user: ' . $request->username);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'password' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            // Generate access token for regular admin
            try {
                $token = $user->createToken('admin-token')->accessToken;
                
                Log::info('Admin token generated successfully for user: ' . $user->username);
                
                // Return successful response with token
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
            } catch (\Exception $e) {
                Log::error('Failed to generate admin token: ' . $e->getMessage());
                throw $e;
            }
        } catch (ValidationException $e) {
            // Handle validation errors
            Log::warning('Admin login validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error('Admin login error: ' . $e->getMessage());
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
            
            Log::info('Admin logged out successfully: ' . $username);
            
            return response()->json([
                'message' => 'Admin logout successful',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin logout error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
