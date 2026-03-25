<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Material;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestSection;
use App\Models\TestSubmission;
use App\Models\TypesOfTestQuestions\TestCorrelationQuestion;
use App\Models\TypesOfTestQuestions\TestCorrectQuestion;
use App\Models\TypesOfTestQuestions\TestGapFillQuestion;
use App\Models\TypesOfTestQuestions\TestMultipleChoiceQuestion;
use App\Models\TypesOfTestQuestions\TestReadingQuestion;
use App\Models\TypesOfTestQuestions\TestRephraseQuestion;
use App\Models\TypesOfTestQuestions\TestReplaceQuestion;
use App\Models\TypesOfTestQuestions\TestSpeakingQuestion;
use App\Models\TypesOfTestQuestions\TestTextCompletionQuestion;
use App\Models\TypesOfTestQuestions\TestWordDerivationQuestion;
use App\Models\TypesOfTestQuestions\TestWordFormationQuestion;
use App\Models\TypesOfTestQuestions\TestWritingQuestion;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function sectionBatchStore(Request $request, $testId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $allTypes = array_unique(array_merge(...array_values(TestSection::ALLOWED_QUESTION_TYPES)));

        $validated = $request->validate([
            'section_type'                                 => ['required', Rule::in(TestSection::TYPES)],
            'title'                                        => 'nullable|string|max:255',
            'instruction_text'                             => 'nullable|string',
            'instruction_files'                            => 'nullable|array',
            'instruction_files.*'                          => 'integer|exists:materials,material_id',
            'order'                                        => 'nullable|integer|min:1',
            'passage'                                      => 'nullable|string',
            'audio_url'                                    => 'nullable|url',
            'audio_material_id'                            => 'nullable|integer|exists:materials,material_id',
            'transcript'                                   => 'nullable|string',
            // questions array
            'questions'                                    => 'nullable|array',
            'questions.*.question_text'                    => 'required_with:questions|string',
            'questions.*.question_type'                    => ['required_with:questions', Rule::in($allTypes)],
            'questions.*.order'                            => 'nullable|integer|min:1',
            'questions.*.instruction_files'                => 'nullable|array',
            'questions.*.instruction_files.*'              => 'integer|exists:materials,material_id',
            // multiple_choice / reading_multiple_choice / listening_multiple_choice
            'questions.*.variants'                         => 'nullable|array',
            'questions.*.variants.*'                       => 'string',
            'questions.*.correct_variant'                  => 'nullable|integer',
            // gap_fill
            'questions.*.with_variants'                    => 'nullable|boolean',
            'questions.*.correct_answers'                  => 'nullable|array',
            'questions.*.correct_answers.*'                => 'string',
            // rephrase / word_formation / replace / correct / word_derivation / reading_question / writing_question / speaking_question
            'questions.*.sample_answer'                    => 'nullable|string',
            'questions.*.base_word'                        => 'nullable|string',
            'questions.*.root_word'                        => 'nullable|string',
            'questions.*.original_text'                    => 'nullable|string',
            'questions.*.incorrect_text'                   => 'nullable|string',
            // text_completion
            'questions.*.full_text'                        => 'nullable|string',
            // correlation
            'questions.*.column_a'                         => 'nullable|array',
            'questions.*.column_a.*'                       => 'string',
            'questions.*.column_b'                         => 'nullable|array',
            'questions.*.column_b.*'                       => 'string',
            'questions.*.correct_pairs'                    => 'nullable|array',
            // speaking_question
            'questions.*.speaking_instruction_files'       => 'nullable|array',
            'questions.*.speaking_instruction_files.*'     => 'integer|exists:materials,material_id',
        ]);

        $sectionType = $validated['section_type'];
        $allowedForSection = TestSection::ALLOWED_QUESTION_TYPES[$sectionType] ?? [];

        // Validate each question type is allowed in this section type before touching the DB
        foreach ($validated['questions'] ?? [] as $index => $q) {
            if (!in_array($q['question_type'], $allowedForSection)) {
                return response()->json([
                    'message'       => "questions.{$index}.question_type '{$q['question_type']}' is not allowed in a {$sectionType} section",
                    'allowed_types' => $allowedForSection,
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($testId, $validated, $sectionType) {
            $section = TestSection::create([
                'test_id'           => $testId,
                'section_type'      => $sectionType,
                'title'             => $validated['title'] ?? null,
                'instruction_text'  => $validated['instruction_text'] ?? null,
                'instruction_files' => $validated['instruction_files'] ?? null,
                'order'             => $validated['order'] ?? null,
                'passage'           => $validated['passage'] ?? null,
                'audio_url'         => $validated['audio_url'] ?? null,
                'audio_material_id' => $validated['audio_material_id'] ?? null,
                'transcript'        => $validated['transcript'] ?? null,
            ]);

            $createdQuestions = [];
            foreach ($validated['questions'] ?? [] as $qData) {
                $question = TestQuestion::create([
                    'test_id'           => $testId,
                    'test_section_id'   => $section->id,
                    'question_text'     => $qData['question_text'],
                    'question_type'     => $qData['question_type'],
                    'order'             => $qData['order'] ?? null,
                    'instruction_files' => $qData['instruction_files'] ?? null,
                ]);

                $this->createQuestionDetail($question, $qData['question_type'], $qData);
                $createdQuestions[] = $question->test_question_id;
            }

            return ['section' => $section, 'question_ids' => $createdQuestions];
        });

        $section = $result['section']->load('questions');

        DatabaseLog::logAction('create', TestSection::class, $section->id, "Batch section created for test #{$testId} with " . count($result['question_ids']) . " questions");

        return response()->json([
            'message' => 'Section created successfully with questions',
            'section' => $section,
        ], 201);
    }

    private function createQuestionDetail(TestQuestion $question, string $type, array $data): void
    {
        $qId = $question->test_question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice'])
                => TestMultipleChoiceQuestion::create([
                    'test_question_id' => $qId,
                    'variants'         => $data['variants'] ?? [],
                    'correct_variant'  => $data['correct_variant'] ?? 0,
                ]),

            $type === 'gap_fill'
                => TestGapFillQuestion::create([
                    'test_question_id' => $qId,
                    'with_variants'    => $data['with_variants'] ?? false,
                    'variants'         => $data['variants'] ?? null,
                    'correct_answers'  => $data['correct_answers'] ?? [],
                ]),

            $type === 'rephrase'
                => TestRephraseQuestion::create([
                    'test_question_id' => $qId,
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'word_formation'
                => TestWordFormationQuestion::create([
                    'test_question_id' => $qId,
                    'base_word'        => $data['base_word'] ?? '',
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'replace'
                => TestReplaceQuestion::create([
                    'test_question_id' => $qId,
                    'original_text'    => $data['original_text'] ?? '',
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'correct'
                => TestCorrectQuestion::create([
                    'test_question_id' => $qId,
                    'incorrect_text'   => $data['incorrect_text'] ?? '',
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'word_derivation'
                => TestWordDerivationQuestion::create([
                    'test_question_id' => $qId,
                    'root_word'        => $data['root_word'] ?? '',
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'text_completion'
                => TestTextCompletionQuestion::create([
                    'test_question_id' => $qId,
                    'full_text'        => $data['full_text'] ?? '',
                    'correct_answers'  => $data['correct_answers'] ?? [],
                ]),

            $type === 'correlation'
                => TestCorrelationQuestion::create([
                    'test_question_id' => $qId,
                    'column_a'         => $data['column_a'] ?? [],
                    'column_b'         => $data['column_b'] ?? [],
                    'correct_pairs'    => $data['correct_pairs'] ?? [],
                ]),

            $type === 'reading_question'
                => TestReadingQuestion::create([
                    'test_question_id' => $qId,
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'writing_question'
                => TestWritingQuestion::create([
                    'test_question_id' => $qId,
                    'sample_answer'    => $data['sample_answer'] ?? null,
                ]),

            $type === 'speaking_question'
                => TestSpeakingQuestion::create([
                    'test_question_id'  => $qId,
                    'instruction_files' => $data['speaking_instruction_files'] ?? null,
                    'sample_answer'     => $data['sample_answer'] ?? null,
                ]),

            default => null,
        };
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
