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
                $token = $superAdmin->createToken('super-admin-token')->accessToken;
                
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
            }
            
            // If not super admin, check database for regular admin
            $user = Admin::where('username', $request->username)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => [
                        'username' => ['The provided credentials are incorrect.']
                    ]
                ], 401);
            }

            // Generate access token for regular admin
            $token = $user->createToken('admin-token')->accessToken;

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
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
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
