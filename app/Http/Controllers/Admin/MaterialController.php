<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
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
            // folder_path: any valid bucket path, e.g. 'common', 'common/sub', 'admin/files/sub', 'teachers/user/private/sub'
            'folder_path'     => 'required|string|max:500',
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

        $folder = $request->input('folder_path');
        if (str_contains($folder, '..') || str_contains($folder, '//') || str_contains($folder, '\\')) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['folder_path' => ['The folder path must not contain path traversal sequences.']],
            ], 422);
        }

        $uploaderId = $request->input('uploader_id') ?? Auth::id();
        $uploader   = User::findOrFail($uploaderId);
        $files      = $request->allFiles();
        $file       = is_array($files['file']) ? $files['file'][0] : $files['file'];

        // Upload directly to the specified path
        $gcsPath = rtrim($folder, '/') . '/' . $file->getClientOriginalName();

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

        DatabaseLog::logAction('create', Material::class, $material->material_id, "Material '{$material->material_name}' uploaded to '{$folder}'");

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
     * Update allowed_users and/or allowed_groups on any material.
     */
    public function updateAccess(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'allowed_users'    => 'nullable|array',
            'allowed_users.*'  => 'integer|exists:users,id',
            'allowed_groups'   => 'nullable|array',
            'allowed_groups.*' => 'integer|exists:groups,group_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $allowedUsers  = $request->input('allowed_users', $material->allowed_users ?? []);
        $allowedGroups = $request->input('allowed_groups', $material->allowed_groups ?? []);

        $material->update([
            'allowed_users'  => array_values(array_unique(array_map('intval', $allowedUsers))),
            'allowed_groups' => array_values(array_unique(array_map('intval', $allowedGroups))),
        ]);

        DatabaseLog::logAction('update', Material::class, $material->material_id, "Access updated for material '{$material->material_name}'");

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

        $materialName = $material->material_name;
        $materialId = $material->material_id;
        $this->gcs->delete($material->gcs_path);
        $material->delete();

        DatabaseLog::logAction('delete', Material::class, $materialId, "Material '{$materialName}' deleted");

        return response()->json(['message' => 'Material deleted successfully']);
    }

    // -------------------------------------------------------------------------
    // Folder management — admins can operate on any path in the bucket
    // -------------------------------------------------------------------------

    /**
     * List the contents (subfolders + files) at a given bucket prefix.
     * Uses GCS delimiter listing — surfaces all virtual directories, even empty ones.
     * GET /api/admin/storage/list?prefix=teachers/teacher1/private/
     */
    public function listObjects(Request $request)
    {
        $prefix   = $request->query('prefix', '');
        $contents = $this->gcs->listContents($prefix);

        return response()->json([
            'message' => 'Contents retrieved successfully',
            'prefix'  => $prefix,
            'folders' => $contents['folders'],
            'files'   => $contents['files'],
        ]);
    }

    /**
     * List immediate subfolders under a given bucket prefix.
     * Uses GCS delimiter listing — finds all virtual directories including empty ones.
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
            'path' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = trim($request->input('path'), '/');
        if (str_contains($path, '..') || str_contains($path, '//') || str_contains($path, '\\')) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['path' => ['The path must not contain path traversal sequences.']],
            ], 422);
        }
        $created = $this->gcs->createFolder($path);

        if (!$created) {
            return response()->json(['message' => 'Folder already exists'], 409);
        }

        DatabaseLog::logAction('create', Material::class, null, "Folder '{$path}/' created in storage");

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
            'path' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $path = trim($request->input('path'), '/');
        if (str_contains($path, '..') || str_contains($path, '//') || str_contains($path, '\\')) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => ['path' => ['The path must not contain path traversal sequences.']],
            ], 422);
        }
        $deleted = $this->gcs->deleteFolder($path);

        if ($deleted === 0) {
            return response()->json(['message' => 'Folder not found or already empty'], 404);
        }

        DatabaseLog::logAction('delete', Material::class, null, "Folder '{$path}/' deleted from storage ({$deleted} objects removed)");

        return response()->json([
            'message'         => 'Folder deleted successfully',
            'objects_deleted' => $deleted,
        ]);
    }

    // -------------------------------------------------------------------------
    // Storage repair — syncs missing Material DB records from GCS
    // -------------------------------------------------------------------------

    /**
     * Scan the bucket for files that exist in GCS but have no Material record,
     * then create the missing records, deriving uploader and folder from the path.
     *
     * Scans:
     *   teachers/{username}/private/...  → uploader = teacher, folder = private[/sub]
     *   common/...                       → uploader = null,    folder = common
     *
     * Skips:
     *   .keep placeholder objects
     *   teachers/{username}/profile/...  (profile pictures, not materials)
     *
     * POST /api/admin/storage/repair
     */
    public function repairMaterials()
    {
        // Index existing paths so we can check in O(1)
        $existingPaths = Material::pluck('gcs_path')->flip()->toArray();

        // Build username → User map (any role; username is unique across all users)
        $usersByUsername = User::all(['id', 'username'])->keyBy('username');

        $created = [];
        $skipped = [];

        // Collect all objects from the relevant top-level prefixes
        $prefixes = ['teachers/', 'common/'];
        foreach ($prefixes as $prefix) {
            $objects = $this->gcs->listFolder($prefix);

            foreach ($objects as $path) {
                // Skip .keep folder placeholders
                if (str_ends_with($path, '.keep')) {
                    continue;
                }

                // Skip if a Material record already exists for this path
                if (isset($existingPaths[$path])) {
                    $skipped[] = $path;
                    continue;
                }

                $parts    = explode('/', $path);
                $filename = end($parts);
                $fileType = $this->guessMimeType(pathinfo($filename, PATHINFO_EXTENSION));

                if (str_starts_with($path, 'teachers/')) {
                    // Expected shape: teachers/{username}/{folder_parts...}/{filename}
                    if (count($parts) < 3) {
                        continue;
                    }

                    $username = $parts[1];

                    // Skip profile pictures — they are not teaching materials
                    if (($parts[2] ?? '') === 'profile') {
                        continue;
                    }

                    $user = $usersByUsername[$username] ?? null;
                    if (!$user) {
                        continue;
                    }

                    // folder = everything between the username and the filename
                    // e.g. teachers/teacherTest/private/corrections/153_1248.md → private/corrections
                    $folderParts = array_slice($parts, 2, count($parts) - 3);
                    $folder      = count($folderParts) ? implode('/', $folderParts) : 'private';

                    Material::create([
                        'material_name'  => $filename,
                        'file_type'      => $fileType,
                        'date_created'   => now(),
                        'authors'        => [$user->id],
                        'allowed_users'  => [],
                        'allowed_groups' => [],
                        'gcs_path'       => $path,
                        'uploader_id'    => $user->id,
                        'folder'         => $folder,
                    ]);

                    $created[] = ['path' => $path, 'uploader' => $username, 'folder' => $folder];

                } elseif (str_starts_with($path, 'common/')) {
                    Material::create([
                        'material_name'  => $filename,
                        'file_type'      => $fileType,
                        'date_created'   => now(),
                        'authors'        => [],
                        'allowed_users'  => [],
                        'allowed_groups' => [],
                        'gcs_path'       => $path,
                        'uploader_id'    => null,
                        'folder'         => 'common',
                    ]);

                    $created[] = ['path' => $path, 'uploader' => null, 'folder' => 'common'];
                }
            }
        }

        DatabaseLog::logAction('create', Material::class, null, 'Storage repair: ' . count($created) . ' missing Material records created.');

        return response()->json([
            'message'       => 'Storage repair completed',
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'created'       => $created,
        ]);
    }

    // -------------------------------------------------------------------------

    private function guessMimeType(string $ext): string
    {
        $map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'mp3'  => 'audio/mpeg',
            'mp4'  => 'video/mp4',
            'wav'  => 'audio/wav',
            'zip'  => 'application/zip',
            'txt'  => 'text/plain',
            'md'   => 'text/markdown',
            'csv'  => 'text/csv',
            'json' => 'application/json',
        ];

        return $map[strtolower($ext)] ?? 'application/octet-stream';
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
