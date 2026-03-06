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
     * List all materials the student has been given access to.
     */
    public function index()
    {
        $studentId = Auth::id();

        // Fetch all materials and filter those where allowed_users contains this student's ID.
        // Using a JSON-contains approach that works across databases.
        $materials = Material::all()->filter(function ($material) use ($studentId) {
            return in_array($studentId, $material->allowed_users ?? []);
        })->values();

        return response()->json([
            'message'   => 'Materials retrieved successfully',
            'materials' => $materials,
        ]);
    }

    /**
     * Get a signed download URL for a material the student has access to.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        $material  = Material::findOrFail($id);

        if (!in_array($studentId, $material->allowed_users ?? [])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $url = $this->gcs->signedUrl($material->gcs_path, 60);

        return response()->json([
            'message'      => 'Material retrieved successfully',
            'material'     => $material,
            'download_url' => $url,
        ]);
    }
}
