<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingAccount;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MeetingAccountController extends Controller
{
    /**
     * List all meeting accounts.
     */
    public function index()
    {
        $accounts = MeetingAccount::with('creator:id,username,email')->get();

        return response()->json([
            'message'  => 'Meeting accounts retrieved successfully',
            'count'    => $accounts->count(),
            'accounts' => $accounts,
        ]);
    }

    /**
     * Create a new meeting account.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'provider'      => 'required|string|in:zoom',
            'account_id'    => 'required|string|max:255',
            'client_id'     => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data               = $validator->validated();
        $data['created_by'] = Auth::id();

        $account = MeetingAccount::create($data);

        return response()->json([
            'message' => 'Meeting account created successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Show a single meeting account (credentials hidden via $hidden).
     */
    public function show($id)
    {
        $account = MeetingAccount::with('creator:id,username,email')->find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        return response()->json([
            'message' => 'Meeting account retrieved successfully',
            'account' => $account,
        ]);
    }

    /**
     * Update a meeting account.
     */
    public function update(Request $request, $id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:255',
            'provider'      => 'sometimes|string|in:zoom',
            'account_id'    => 'sometimes|string|max:255',
            'client_id'     => 'sometimes|string|max:255',
            'client_secret' => 'sometimes|string|max:255',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $account->update($validator->validated());

        return response()->json([
            'message' => 'Meeting account updated successfully',
            'account' => $account->fresh(),
        ]);
    }

    /**
     * Delete a meeting account.
     */
    public function destroy($id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        $account->delete();

        return response()->json(['message' => 'Meeting account deleted successfully']);
    }

    /**
     * Test the credentials by attempting to fetch a Zoom access token.
     */
    public function test(ZoomService $zoom, $id)
    {
        $account = MeetingAccount::find($id);

        if (!$account) {
            return response()->json(['message' => 'Meeting account not found'], 404);
        }

        try {
            $token = $zoom->getAccessToken($account);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Credentials test failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Credentials are valid — access token obtained successfully',
        ]);
    }
}
