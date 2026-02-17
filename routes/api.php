<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloController;
use Illuminate\Http\Request;

/**
 * API Routes
 * 
 * All routes here are automatically prefixed with /api
 * So this route will be accessible at: http://localhost:8000/api/hello
 */

Route::get('/hello', [HelloController::class, 'index']);

// Detailed debug route for token validation
Route::get('/auth-debug', function (Request $request) {
    $token = $request->bearerToken();
    $tokenParts = $token ? explode('.', $token) : [];
    $tokenPartsCount = count($tokenParts);
    $tokenHeader = $tokenPartsCount >= 1 ? base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenParts[0]))) : null;
    $tokenPayload = $tokenPartsCount >= 2 ? base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenParts[1]))) : null;
    
    return response()->json([
        'message' => 'Authentication debug route',
        'authenticated' => auth()->check(),
        'guard' => auth()->getDefaultDriver(),
        'available_guards' => array_keys(config('auth.guards')),
        'token_present' => !empty($token),
        'token_format_valid' => $tokenPartsCount >= 3,
        'token_header' => $tokenHeader ? json_decode($tokenHeader, true) : null,
        'token_payload' => $tokenPayload ? json_decode($tokenPayload, true) : null,
        'request_headers' => $request->headers->all(),
        'auth_header' => $request->header('Authorization'),
    ]);
});

// Special route to analyze the working token
Route::get('/analyze-working-token', function () {
    // The working token
    $workingToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMDAzYjczYjUyMzVmN2NhZGNhNmE0ZTUxZWJhMGM5MWIxZWZmZmRkYjQzNTEzNjE0NDVlNWYzYWQ1NjIwNDMzNjM5ODJkMDUyYjk4NjFiZGYiLCJpYXQiOjE3NzA5MDMwMjMuNDg0MTY5LCJuYmYiOjE3NzA5MDMwMjMuNDg0MTcyLCJleHAiOjE4MDI0MzkwMjMuNDY5MTE1LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.MO-tPbK9x6MCjxPWDIkQrfY9F2QUQFZdMk_Fm5NQL72N4b-RNFNUEQRKb_Z8B7RiSdp3Y33Q9sMAO3qEwVqwL7wSnLs-hDHNeRcGGGKodoRCIy0U8EwGH2qFMFGaS0bH0mTfg3N-2kahqpqZoqLAiT1Poo9FUS3PkRWUaFEXmPOhbhpg6nB3u6ZZPXQU7Ztkyrw9bfJWiBvUrDZSDZBx9c_v0lv4o-md4S6Zbcs4qJmWQ610EMd7TtgImAwyXjiV2oA3cACbsW5C-he07RFBmmNGMWmWi3TEND4AbzoOfMF4sxbnf3kJI7Y-qFM2Rc0TtfAmlCE2B6tyTHJftXrdb5AJupOJhefYNqBph6OmW0JmUa1V6DGRBL4fd4BPgV7Gi8UwkVdxs53FjnUMdh9YSJcjLRR0azBTXmIMMMfk3x25Vxh6ff0B1nZRCVhcnguhhLVHau65NHXmSVZY8J_DA3p8tiIaTTvia3k2UMG-ccTme93z5cTdPvjBAcpmCRwUKS-QogBnA-dI23WrZxoyk8-hLnCK83su7S3UroZLDCnS0wOp69K7jp5RnVPg1RTZQ-uFhQlELVdSQIwZhGBbZIgan56oQ71SBZ0Q-yfBsMQlbB-JMmvD49SvLUqkm-uCDL7fjEdaExkBB42QREKti7CWwy8x02ezVLG2eM7vpe0';
    
    // Parse the token
    $tokenParts = explode('.', $workingToken);
    $tokenHeader = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenParts[0]))), true);
    $tokenPayload = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenParts[1]))), true);
    
    // Get a new token from login
    try {
        $admin = new \App\Models\Admin();
        $admin->id = 1; // Same ID as in the working token
        $admin->username = 'test_admin';
        $admin->role = 'admin';
        
        $newToken = $admin->createToken('test-token')->accessToken;
        $newTokenParts = explode('.', $newToken);
        $newTokenHeader = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $newTokenParts[0]))), true);
        $newTokenPayload = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $newTokenParts[1]))), true);
        
        return response()->json([
            'message' => 'Token comparison',
            'working_token' => [
                'header' => $tokenHeader,
                'payload' => $tokenPayload,
            ],
            'new_token' => [
                'header' => $newTokenHeader,
                'payload' => $newTokenPayload,
                'full_token' => $newToken,
            ],
            'differences' => [
                'header_diff' => array_diff_assoc($tokenHeader, $newTokenHeader),
                'payload_diff' => array_diff_assoc($tokenPayload, $newTokenPayload),
            ],
            'passport_keys_path' => storage_path('oauth-*.key'),
            'passport_keys_exist' => [
                'private' => file_exists(storage_path('oauth-private.key')),
                'public' => file_exists(storage_path('oauth-public.key')),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error generating new token',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'working_token' => [
                'header' => $tokenHeader,
                'payload' => $tokenPayload,
            ],
        ]);
    }
});

// Route that uses the working token directly
Route::get('/use-working-token', function () {
    // Return the working token for the user to use
    return response()->json([
        'message' => 'Use this token for authentication',
        'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiMDAzYjczYjUyMzVmN2NhZGNhNmE0ZTUxZWJhMGM5MWIxZWZmZmRkYjQzNTEzNjE0NDVlNWYzYWQ1NjIwNDMzNjM5ODJkMDUyYjk4NjFiZGYiLCJpYXQiOjE3NzA5MDMwMjMuNDg0MTY5LCJuYmYiOjE3NzA5MDMwMjMuNDg0MTcyLCJleHAiOjE4MDI0MzkwMjMuNDY5MTE1LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.MO-tPbK9x6MCjxPWDIkQrfY9F2QUQFZdMk_Fm5NQL72N4b-RNFNUEQRKb_Z8B7RiSdp3Y33Q9sMAO3qEwVqwL7wSnLs-hDHNeRcGGGKodoRCIy0U8EwGH2qFMFGaS0bH0mTfg3N-2kahqpqZoqLAiT1Poo9FUS3PkRWUaFEXmPOhbhpg6nB3u6ZZPXQU7Ztkyrw9bfJWiBvUrDZSDZBx9c_v0lv4o-md4S6Zbcs4qJmWQ610EMd7TtgImAwyXjiV2oA3cACbsW5C-he07RFBmmNGMWmWi3TEND4AbzoOfMF4sxbnf3kJI7Y-qFM2Rc0TtfAmlCE2B6tyTHJftXrdb5AJupOJhefYNqBph6OmW0JmUa1V6DGRBL4fd4BPgV7Gi8UwkVdxs53FjnUMdh9YSJcjLRR0azBTXmIMMMfk3x25Vxh6ff0B1nZRCVhcnguhhLVHau65NHXmSVZY8J_DA3p8tiIaTTvia3k2UMG-ccTme93z5cTdPvjBAcpmCRwUKS-QogBnA-dI23WrZxoyk8-hLnCK83su7S3UroZLDCnS0wOp69K7jp5RnVPg1RTZQ-uFhQlELVdSQIwZhGBbZIgan56oQ71SBZ0Q-yfBsMQlbB-JMmvD49SvLUqkm-uCDL7fjEdaExkBB42QREKti7CWwy8x02ezVLG2eM7vpe0',
        'token_type' => 'Bearer',
        'instructions' => 'Use this token in your Authorization header for all protected routes',
    ]);
});

// Test route with auth:api middleware
Route::middleware('auth:api')->get('/auth-test-api', function (Request $request) {
    return response()->json([
        'message' => 'Protected API route accessed successfully',
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver(),
    ]);
});

// Test route with auth middleware only
Route::middleware('auth')->get('/auth-test-default', function (Request $request) {
    return response()->json([
        'message' => 'Protected default route accessed successfully',
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver(),
    ]);
});
