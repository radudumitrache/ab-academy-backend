<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Material;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\TestSubmission;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TestController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    // ── Test CRUD ─────────────────────────────────────────────────────────────

    public function index()
    {
        $tests = Test::with('teacher:id,username')
            ->withCount('allQuestions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Tests retrieved successfully',
            'count'   => $tests->count(),
            'tests'   => $tests,
        ]);
    }

    public function show($id)
    {
        $test = Test::with('teacher:id,username')->find($id);

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
            'sections.questions.writingQuestionDetails',
            'sections.questions.speakingQuestionDetails',
        ]);

        return response()->json([
            'message' => 'Test retrieved successfully',
            'test'    => $this->resolveSignedUrls($test),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_title'        => 'required|string|max:255',
            'test_description'  => 'nullable|string',
            'due_date'          => 'nullable|date_format:Y-m-d',
            'test_teacher'      => 'nullable|integer|exists:users,id',
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $validated['test_teacher'] = $validated['test_teacher'] ?? Auth::id();
        $validated['date_created'] = now();

        $test = Test::create($validated);

        DatabaseLog::logAction('create', Test::class, $test->id, "Test '{$test->test_title}' created");

        return response()->json([
            'message' => 'Test created successfully',
            'test'    => $test,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $test = Test::find($id);

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

        DatabaseLog::logAction('update', Test::class, $test->id, "Test '{$test->test_title}' updated");

        return response()->json([
            'message' => 'Test updated successfully',
            'test'    => $test->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $test = Test::find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $testTitle = $test->test_title;
        $test->delete();

        DatabaseLog::logAction('delete', Test::class, $id, "Test '{$testTitle}' deleted");

        return response()->json(['message' => 'Test deleted successfully']);
    }

    public function assignStudents(Request $request, $id)
    {
        $test = Test::find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $test->update([
            'people_assigned' => $validated['people_assigned'] ?? [],
            'groups_assigned' => $validated['groups_assigned'] ?? [],
        ]);

        DatabaseLog::logAction('update', Test::class, $test->id, "Students assigned to test '{$test->test_title}'");

        return response()->json([
            'message' => 'Students assigned successfully',
            'test'    => $test->fresh(),
        ]);
    }

    // ── Sections ──────────────────────────────────────────────────────────────

    public function sectionIndex($testId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $sections = TestSection::where('test_id', $testId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return response()->json([
            'message'  => 'Sections retrieved successfully',
            'sections' => $sections,
        ]);
    }

    public function sectionStore(Request $request, $testId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(TestSection::TYPES)],
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            'passage'             => 'nullable|string',
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);

        $section = TestSection::create([
            'test_id'           => $testId,
            'section_type'      => $validated['section_type'],
            'title'             => $validated['title'] ?? null,
            'instruction_text'  => $validated['instruction_text'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
            'order'             => $validated['order'] ?? null,
            'passage'           => $validated['passage'] ?? null,
            'audio_url'         => $validated['audio_url'] ?? null,
            'audio_material_id' => $validated['audio_material_id'] ?? null,
            'transcript'        => $validated['transcript'] ?? null,
        ]);

        DatabaseLog::logAction('create', TestSection::class, $section->id, "Section created for test #{$testId}");

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    public function sectionUpdate(Request $request, $testId, $sectionId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $section = TestSection::where('test_id', $testId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $validated = $request->validate([
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            'passage'             => 'nullable|string',
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);

        $section->update(array_filter($validated, fn ($v) => !is_null($v)));

        DatabaseLog::logAction('update', TestSection::class, $section->id, "Section #{$sectionId} updated for test #{$testId}");

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    public function sectionDestroy($testId, $sectionId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $section = TestSection::where('test_id', $testId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        DatabaseLog::logAction('delete', TestSection::class, $sectionId, "Section #{$sectionId} deleted from test #{$testId}");

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // ── Submissions (view only) ───────────────────────────────────────────────

    public function submissions($testId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submissions = TestSubmission::where('test_id', $testId)
            ->with(['student:id,username,email', 'responses'])
            ->get();

        return response()->json([
            'message'     => 'Submissions retrieved successfully',
            'count'       => $submissions->count(),
            'submissions' => $submissions,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function resolveSignedUrls(Test $test): array
    {
        $materialIds = [];
        foreach ($test->sections as $section) {
            foreach ((array) $section->instruction_files as $mid) { $materialIds[] = $mid; }
            if ($section->audio_material_id) { $materialIds[] = $section->audio_material_id; }
            foreach ($section->questions as $question) {
                foreach ((array) $question->instruction_files as $mid) { $materialIds[] = $mid; }
            }
        }

        $signedUrls = [];
        if (!empty($materialIds)) {
            $materials = Material::whereIn('material_id', array_unique($materialIds))->get()->keyBy('material_id');
            foreach ($materials as $mid => $material) {
                try { $signedUrls[$mid] = $this->gcs->signedUrl($material->gcs_path, 60); }
                catch (\Throwable) { $signedUrls[$mid] = null; }
            }
        }

        $data = $test->toArray();
        foreach ($data['sections'] as &$sd) {
            $sd['instruction_file_urls'] = array_map(fn($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null], (array)($sd['instruction_files'] ?? []));
            if (!empty($sd['audio_material_id'])) { $sd['audio_url_signed'] = $signedUrls[$sd['audio_material_id']] ?? null; }
            foreach ($sd['questions'] as &$qd) {
                $qd['instruction_file_urls'] = array_map(fn($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null], (array)($qd['instruction_files'] ?? []));
            }
            unset($qd);
        }
        unset($sd);

        return $data;
    }
}
