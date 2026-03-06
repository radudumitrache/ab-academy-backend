<?php

namespace App\Http\Controllers\Admin;

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
     * List all materials across all teachers and the common folder.
     */
    public function index()
    {
        $materials = Material::with('uploader:id,username')->latest()->get();

        return response()->json([
            'message'   => 'Materials retrieved successfully',
            'materials' => $materials,
        ]);
    }

    /**
     * Upload a file to any folder on behalf of any teacher (or common).
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'            => 'required|file|max:102400',
            'material_name'   => 'nullable|string|max:255',
            'folder'          => 'required|in:private,common',
            'uploader_id'     => 'nullable|integer|exists:users,id',
            'allowed_users'   => 'nullable|array',
            'allowed_users.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $uploaderId = $request->input('uploader_id') ?? Auth::id();
        $uploader   = User::findOrFail($uploaderId);
        $file       = $request->file('file');
        $folder     = $request->input('folder');

        $gcsPath = $folder === 'common'
            ? 'common/' . $file->getClientOriginalName()
            : "teachers/{$uploader->username}/private/" . $file->getClientOriginalName();

        $gcsPath = $this->uniquePath($gcsPath);

        $this->gcs->upload($file, $gcsPath);

        $material = Material::create([
            'material_name' => $request->input('material_name') ?? $file->getClientOriginalName(),
            'file_type'     => $file->getClientMimeType(),
            'date_created'  => now(),
            'authors'       => [$uploaderId],
            'allowed_users' => $request->input('allowed_users') ?? [],
            'gcs_path'      => $gcsPath,
            'uploader_id'   => $uploaderId,
            'folder'        => $folder,
        ]);

        return response()->json([
            'message'  => 'Material uploaded successfully',
            'material' => $material,
        ], 201);
    }

    /**
     * Get material details and a signed download URL.
     */
    public function show($id)
    {
        $material = Material::with('uploader:id,username')->findOrFail($id);
        $url      = $this->gcs->signedUrl($material->gcs_path, 60);

        return response()->json([
            'message'      => 'Material retrieved successfully',
            'material'     => $material,
            'download_url' => $url,
        ]);
    }

    /**
     * Update allowed_users on any material.
     */
    public function updateAccess(Request $request, $id)
    {
        $material = Material::findOrFail($id);

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
     * Delete any material from GCS and the database.
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);

        $this->gcs->delete($material->gcs_path);
        $material->delete();

        return response()->json(['message' => 'Material deleted successfully']);
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
