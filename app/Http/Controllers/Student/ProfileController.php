<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Return the authenticated student's profile.
     */
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'profile' => [
                'id'           => $user->id,
                'username'     => $user->username,
                'email'        => $user->email,
                'telephone'    => $user->telephone,
                'address'      => $user->address,
                'street'       => $user->street,
                'house_number' => $user->house_number,
                'city'         => $user->city,
                'county'       => $user->county,
                'country'      => $user->country,
                'occupation'   => $user->occupation,
                'role'         => $user->role,
            ],
        ]);
    }

    /**
     * Update the authenticated student's profile details.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email'        => 'sometimes|email|unique:users,email,' . $user->id,
            'telephone'    => 'nullable|string|max:20',
            'address'      => 'nullable|string|max:255',
            'street'       => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'city'         => 'nullable|string|max:100',
            'county'       => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
            'occupation'   => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => [
                'id'           => $user->id,
                'username'     => $user->username,
                'email'        => $user->email,
                'telephone'    => $user->telephone,
                'address'      => $user->address,
                'street'       => $user->street,
                'house_number' => $user->house_number,
                'city'         => $user->city,
                'county'       => $user->county,
                'country'      => $user->country,
                'occupation'   => $user->occupation,
            ],
        ]);
    }

    /**
     * Change the student's password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return response()->json(['message' => 'Password changed successfully']);
    }
}
