<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\GcsService;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all materials the student has been explicitly granted access to.
     * Stale DB records (file deleted from GCS) are auto-removed.
     */
    public function index()
    {
        $studentId = Auth::id();
        $groupIds  = $this->studentGroupIds($studentId);

        $materials = Material::all()->filter(function ($material) use ($studentId, $groupIds) {
            if (!$this->hasAccess($material, $studentId, $groupIds)) {
                return false;
            }
            if (!$this->gcs->objectExists($material->gcs_path)) {
                $material->delete();
                return false;
            }
            return true;
        })->values();

        return response()->json([
            'message'   => 'Materials retrieved successfully',
            'materials' => $materials,
        ]);
    }

    /**
     * Get a signed download URL for a material the student has been explicitly granted access to.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        $groupIds  = $this->studentGroupIds($studentId);
        $material  = Material::findOrFail($id);

        if (!$this->hasAccess($material, $studentId, $groupIds)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$this->gcs->objectExists($material->gcs_path)) {
            $material->delete();
            return response()->json(['message' => 'Material not found'], 404);
        }

        $url = $this->gcs->signedUrl($material->gcs_path, 60);

        return response()->json([
            'message'      => 'Material retrieved successfully',
            'material'     => $material,
            'download_url' => $url,
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Return the group IDs the student belongs to.
     */
    private function studentGroupIds(int $studentId): array
    {
        return \App\Models\Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();
    }

    /**
     * Check whether a student has access to a material.
     * Access is granted only via explicit allowed_users or allowed_groups grants.
     */
    private function hasAccess(Material $material, int $studentId, array $groupIds): bool
    {
        if (in_array($studentId, $material->allowed_users ?? [])) {
            return true;
        }

        foreach ($groupIds as $gid) {
            if (in_array($gid, $material->allowed_groups ?? [])) {
                return true;
            }
        }

        return false;
    }
}
