<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\User;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * Create the GCS folder structure for the authenticated teacher.
     * Creates: teachers/{username}/private/ and teachers/{username}/profile/
     * Safe to call multiple times — skips folders that already exist.
     */
    public function setupStorage()
    {
        $user    = Auth::user();
        $created = $this->gcs->createTeacherFolders($user->username);

        return response()->json([
            'message'         => 'Storage setup completed',
            'username'        => $user->username,
            'folders_created' => $created,
            'structure'       => [
                "teachers/{$user->username}/private/",
                "teachers/{$user->username}/profile/",
            ],
        ]);
    }

    /**
     * List materials uploaded by this teacher plus all common-folder materials.
     */
    public function index()
    {
        $teacherId = Auth::id();

        $materials = Material::where(function ($q) use ($teacherId) {
            $q->where('uploader_id', $teacherId)
              ->orWhere('folder', 'common');
        })->latest()->get();

        return response()->json([
            'message'   => 'Materials retrieved successfully',
            'materials' => $materials,
        ]);
    }

    /**
     * Upload a file to the teacher's private folder or the common folder.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'            => 'required|file|max:102400',
            'material_name'   => 'nullable|string|max:255',
            'folder'          => 'required|in:private,common',
            'allowed_users'   => 'nullable|array',
            'allowed_users.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user   = Auth::user();
        $file   = $request->file('file');
        $folder = $request->input('folder');

        $gcsPath = $folder === 'common'
            ? 'common/' . $file->getClientOriginalName()
            : "teachers/{$user->username}/private/" . $file->getClientOriginalName();

        $gcsPath = $this->uniquePath($gcsPath);

        $this->gcs->upload($file, $gcsPath);

        $material = Material::create([
            'material_name' => $request->input('material_name') ?? $file->getClientOriginalName(),
            'file_type'     => $file->getClientMimeType(),
            'date_created'  => now(),
            'authors'       => [$user->id],
            'allowed_users' => $request->input('allowed_users') ?? [],
            'gcs_path'      => $gcsPath,
            'uploader_id'   => $user->id,
            'folder'        => $folder,
        ]);

        return response()->json([
            'message'  => 'Material uploaded successfully',
            'material' => $material,
        ], 201);
    }

    /**
     * Get a material and a signed download URL.
     * Teacher must own the file or it must be in the common folder.
     */
    public function show($id)
    {
        $teacherId = Auth::id();
        $material  = Material::findOrFail($id);

        if ($material->uploader_id !== $teacherId && $material->folder !== 'common') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $url = $this->gcs->signedUrl($material->gcs_path, 60);

        return response()->json([
            'message'      => 'Material retrieved successfully',
            'material'     => $material,
            'download_url' => $url,
        ]);
    }

    /**
     * Update the allowed_users list on a material the teacher owns.
     */
    public function updateAccess(Request $request, $id)
    {
        $teacherId = Auth::id();
        $material  = Material::findOrFail($id);

        if ($material->uploader_id !== $teacherId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'allowed_users'   => 'required|array',
            'allowed_users.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $material->update(['allowed_users' => $request->input('allowed_users')]);

        return response()->json([
            'message'  => 'Access updated successfully',
            'material' => $material,
        ]);
    }

    /**
     * Delete a material the teacher owns (removes from GCS and DB).
     */
    public function destroy($id)
    {
        $teacherId = Auth::id();
        $material  = Material::findOrFail($id);

        if ($material->uploader_id !== $teacherId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->gcs->delete($material->gcs_path);
        $material->delete();

        return response()->json(['message' => 'Material deleted successfully']);
    }

    /**
     * Upload or replace the teacher's profile picture.
     * Stored at: teachers/{username}/profile/profile_picture.{ext}
     * The old picture (any extension) is deleted from GCS before uploading the new one.
     */
    public function uploadProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $file = $request->file('file');
        $ext  = $file->getClientOriginalExtension();
        $path = "teachers/{$user->username}/profile/profile_picture.{$ext}";

        // Delete the old profile picture from GCS if one exists
        if ($user->profile_picture_path) {
            $this->gcs->delete($user->profile_picture_path);
        }

        $this->gcs->upload($file, $path);

        $user->update(['profile_picture_path' => $path]);

        return response()->json([
            'message'              => 'Profile picture uploaded successfully',
            'profile_picture_path' => $path,
        ]);
    }

    /**
     * Get a signed download URL for the teacher's profile picture.
     */
    public function getProfilePicture()
    {
        $user = Auth::user();

        if (!$user->profile_picture_path) {
            return response()->json(['message' => 'No profile picture set'], 404);
        }

        $url = $this->gcs->signedUrl($user->profile_picture_path, 60);

        return response()->json([
            'message'              => 'Profile picture retrieved successfully',
            'profile_picture_path' => $user->profile_picture_path,
            'url'                  => $url,
        ]);
    }

    // -------------------------------------------------------------------------

    private function uniquePath(string $path): string
    {
        $ext     = pathinfo($path, PATHINFO_EXTENSION);
        $base    = $ext ? substr($path, 0, -(strlen($ext) + 1)) : $path;
        $attempt = $path;
        $i       = 1;

        while (Material::where('gcs_path', $attempt)->exists()) {
            $attempt = $ext ? "{$base}_{$i}.{$ext}" : "{$base}_{$i}";
            $i++;
        }

        return $attempt;
    }
}
