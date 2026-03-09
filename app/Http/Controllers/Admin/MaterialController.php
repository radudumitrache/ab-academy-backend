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
            'folder'          => 'required|in:private,common,admin',
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
        $files      = $request->allFiles();
        $file       = is_array($files['file']) ? $files['file'][0] : $files['file'];
        $folder     = $request->input('folder');

        if ($folder === 'common') {
            $gcsPath = 'common/' . $file->getClientOriginalName();
        } elseif ($folder === 'admin') {
            $this->gcs->createAdminFolders();
            $gcsPath = 'admin/files/' . $file->getClientOriginalName();
        } else {
            $gcsPath = "teachers/{$uploader->username}/private/" . $file->getClientOriginalName();
        }

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
    // Folder management — admins can operate on any path in the bucket
    // -------------------------------------------------------------------------

    /**
     * List all objects under a given bucket prefix.
     * GET /api/admin/storage/list?prefix=teachers/teacher1/private/
     */
    public function listObjects(Request $request)
    {
        $prefix  = $request->query('prefix', '');
        $objects = $this->gcs->listFolder($prefix);

        return response()->json([
            'message' => 'Objects retrieved successfully',
            'prefix'  => $prefix,
            'objects' => $objects,
        ]);
    }

    /**
     * List immediate subfolders under a given bucket prefix.
     * GET /api/admin/storage/folders?prefix=teachers/teacher1/private/
     */
    public function listFolders(Request $request)
    {
        $prefix  = $request->query('prefix', '');
        $folders = $this->gcs->listSubfolders($prefix);

        return response()->json([
            'message' => 'Folders retrieved successfully',
            'prefix'  => $prefix,
            'folders' => $folders,
        ]);
    }

    /**
     * Create a folder (placeholder .keep object) at any path in the bucket.
     * POST /api/admin/storage/folders  { "path": "teachers/teacher1/private/new-folder" }
     */
    public function createFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => ['required', 'string', 'max:500', 'regex:/^[a-zA-Z0-9_\-\/\.]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path    = trim($request->input('path'), '/');
        $created = $this->gcs->createFolder($path);

        if (!$created) {
            return response()->json(['message' => 'Folder already exists'], 409);
        }

        return response()->json([
            'message' => 'Folder created successfully',
            'path'    => $path . '/',
        ], 201);
    }

    /**
     * Delete a folder and all its contents at any path in the bucket.
     * DELETE /api/admin/storage/folders  { "path": "teachers/teacher1/private/old-folder" }
     */
    public function deleteFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => ['required', 'string', 'max:500', 'regex:/^[a-zA-Z0-9_\-\/\.]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path    = trim($request->input('path'), '/');
        $deleted = $this->gcs->deleteFolder($path);

        if ($deleted === 0) {
            return response()->json(['message' => 'Folder not found or already empty'], 404);
        }

        return response()->json([
            'message'         => 'Folder deleted successfully',
            'objects_deleted' => $deleted,
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
