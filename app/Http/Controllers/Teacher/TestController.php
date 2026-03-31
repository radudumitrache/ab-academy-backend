<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Test;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all tests created by the authenticated teacher.
     */
    public function index()
    {
        $tests = Test::where('test_teacher', Auth::id())
            ->withCount('allQuestions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Tests retrieved successfully',
            'count'   => $tests->count(),
            'tests'   => $tests,
        ]);
    }

    /**
     * Show a single test with all sections, questions and their detail records.
     * Material IDs in instruction_files and audio_material_id are resolved to signed URLs.
     */
    public function show($id)
    {
        $test = Test::where('test_teacher', Auth::id())->find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $test->load([
            'sections.questions.multipleChoiceDetails',
            'sections.questions.gapFillDetails',
            'sections.questions.rephraseDetails',
            'sections.questions.wordFormationDetails',
            'sections.questions.replaceDetails',
            'sections.questions.correctDetails',
            'sections.questions.wordDerivationDetails',
            'sections.questions.textCompletionDetails',
            'sections.questions.correlationDetails',
            'sections.questions.readingQuestionDetails',
        ]);

        // Collect all Material IDs referenced
        $materialIds = [];
        foreach ($test->sections as $section) {
            foreach ((array) $section->instruction_files as $mid) {
                $materialIds[] = $mid;
            }
            if ($section->audio_material_id) {
                $materialIds[] = $section->audio_material_id;
            }
            foreach ($section->questions as $question) {
                foreach ((array) $question->instruction_files as $mid) {
                    $materialIds[] = $mid;
                }
            }
        }

        // Resolve Material IDs to signed URLs in one batch
        $signedUrls = [];
        if (!empty($materialIds)) {
            $materials = Material::whereIn('material_id', array_unique($materialIds))->get()->keyBy('material_id');
            foreach ($materials as $mid => $material) {
                try {
                    $signedUrls[$mid] = $this->gcs->signedUrl($material->gcs_path, 60);
                } catch (\Throwable) {
                    $signedUrls[$mid] = null;
                }
            }
        }

        // Attach resolved URLs to each section and question
        $testData = $test->toArray();
        foreach ($testData['sections'] as &$sectionData) {
            $sectionData['instruction_file_urls'] = array_map(
                fn ($mid) => ['material_id' => $mid, 'url' => $signedUrls[$mid] ?? null],
                (array) ($sectionData['instruction_files'] ?? [])
            );
            if (!empty($sectionData['audio_material_id'])) {
                $sectionData['audio_url_signed'] = $signedUrls[$sectionData['audio_material_id']] ?? null;
            }
            foreach ($sectionData['questions'] as &$questionData) {
                $questionData['instruction_file_urls'] = array_map(
                    fn ($mid) => ['material_id' => $mid, 'url' => $signedUrls[$mid] ?? null],
                    (array) ($questionData['instruction_files'] ?? [])
                );
            }
            unset($questionData);
        }
        unset($sectionData);

        return response()->json([
            'message' => 'Test retrieved successfully',
            'test'    => $testData,
        ]);
    }

    /**
     * Create a new test.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_title'       => 'required|string|max:255',
            'test_description' => 'nullable|string',
            'due_date'         => 'nullable|date_format:Y-m-d',
            'people_assigned'  => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'  => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $validated['test_teacher'] = Auth::id();
        $validated['date_created'] = now();

        $test = Test::create($validated);

        return response()->json([
            'message' => 'Test created successfully',
            'test'    => $test,
        ], 201);
    }

    /**
     * Update a test. Only the owning teacher may update.
     */
    public function update(Request $request, $id)
    {
        $test = Test::where('test_teacher', Auth::id())->find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'test_title'        => 'sometimes|string|max:255',
            'test_description'  => 'nullable|string',
            'due_date'          => 'sometimes|date_format:Y-m-d',
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $test->update($validated);

        return response()->json([
            'message' => 'Test updated successfully',
            'test'    => $test->fresh(),
        ]);
    }

    /**
     * Delete a test. Sections and questions cascade-delete.
     */
    public function destroy($id)
    {
        $test = Test::where('test_teacher', Auth::id())->find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $test->delete();

        return response()->json(['message' => 'Test deleted successfully']);
    }

    /**
     * Assign (or replace) the student/group list on a test.
     */
    public function assignStudents(Request $request, $id)
    {
        $test = Test::where('test_teacher', Auth::id())->find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
            'is_global'         => 'nullable|boolean',
        ]);

        $test->update([
            'people_assigned' => $validated['people_assigned'] ?? [],
            'groups_assigned' => $validated['groups_assigned'] ?? [],
            'is_global'       => $validated['is_global'] ?? false,
        ]);

        return response()->json([
            'message' => 'Students assigned successfully',
            'test'    => $test->fresh(),
        ]);
    }
}
