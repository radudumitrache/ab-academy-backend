<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * Return the authenticated student's profile.
     */
    public function show()
    {
        $user = Auth::user();

        $profilePictureUrl = null;
        if ($user->profile_picture_path) {
            $profilePictureUrl = $this->gcs->signedUrl($user->profile_picture_path, 60);
        }

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'profile' => [
                'id'                  => $user->id,
                'username'            => $user->username,
                'email'               => $user->email,
                'telephone'           => $user->telephone,
                'address'             => $user->address,
                'street'              => $user->street,
                'house_number'        => $user->house_number,
                'city'                => $user->city,
                'county'              => $user->county,
                'country'             => $user->country,
                'occupation'          => $user->occupation,
                'role'                => $user->role,
                'profile_picture_url' => $profilePictureUrl,
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
            'username'     => 'sometimes|string|max:255|unique:users,username,' . $user->id,
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

        $user->refresh();

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
     * Upload or replace the student's profile picture.
     * Stored at: students/{username}/profile/profile_picture.{ext}
     * Creates the GCS folder structure on first upload if it doesn't exist.
     */
    public function uploadProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();
        $path = "students/{$user->username}/profile/profile_picture.{$ext}";

        // Ensure the student's GCS folder structure exists
        $this->gcs->createStudentFolders($user->username);

        // Delete the old profile picture from GCS if one exists
        if ($user->profile_picture_path) {
            $this->gcs->delete($user->profile_picture_path);
        }

        $this->gcs->upload($file, $path);
        $user->update(['profile_picture_path' => $path]);

        $url = $this->gcs->signedUrl($path, 60);

        return response()->json([
            'message'             => 'Profile picture uploaded successfully',
            'profile_picture_url' => $url,
        ]);
    }

    /**
     * Get a signed download URL for the student's profile picture.
     */
    public function getProfilePicture()
    {
        $user = Auth::user();

        if (!$user->profile_picture_path) {
            return response()->json(['message' => 'No profile picture set'], 404);
        }

        $url = $this->gcs->signedUrl($user->profile_picture_path, 60);

        return response()->json([
            'message'             => 'Profile picture retrieved successfully',
            'profile_picture_url' => $url,
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
