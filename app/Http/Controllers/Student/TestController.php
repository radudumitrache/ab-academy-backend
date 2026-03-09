<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Material;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\TestQuestionResponse;
use App\Services\AchievementService;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function __construct(
        private GcsService $gcs,
        private AchievementService $achievements,
    ) {}

    /**
     * List all tests assigned to the authenticated student.
     */
    public function index()
    {
        $studentId = Auth::id();
        $groupIds  = $this->studentGroupIds($studentId);

        $tests = Test::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', $gid);
            }
        })
        ->orderByDesc('due_date')
        ->get();

        $submissionMap = TestSubmission::where('student_id', $studentId)
            ->whereIn('test_id', $tests->pluck('id'))
            ->get()
            ->keyBy('test_id');

        $result = $tests->map(function ($test) use ($submissionMap) {
            $data = $test->toArray();
            $sub  = $submissionMap->get($test->id);
            $data['submission_status'] = $sub ? $sub->status : 'not_started';
            $data['submitted_at']      = $sub ? $sub->submitted_at : null;
            $data['grade']             = $sub ? $sub->grade : null;
            $data['observation']       = $sub ? $sub->observation : null;
            return $data;
        });

        return response()->json([
            'message' => 'Tests retrieved successfully',
            'count'   => $result->count(),
            'tests'   => $result,
        ]);
    }

    /**
     * Show a single test with all sections and questions.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        $test      = $this->findAssignedTest($studentId, $id);

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

        $testData = $this->resolveSignedUrls($test);

        $sub = TestSubmission::where('test_id', $id)
            ->where('student_id', $studentId)
            ->with('responses')
            ->first();

        $testData['submission_status'] = $sub ? $sub->status : 'not_started';
        $testData['submitted_at']      = $sub ? $sub->submitted_at : null;
        $testData['grade']             = $sub ? $sub->grade : null;
        $testData['observation']       = $sub ? $sub->observation : null;
        $testData['responses'] = $sub
            ? $sub->responses->map(function ($r) {
                $response = [
                    'question_id'         => $r->related_question,
                    'answer'              => $r->answer,
                    'grade'               => $r->grade,
                    'observation'         => $r->observation,
                    'correction_file_url' => null,
                ];
                if ($r->correction_file_path) {
                    try {
                        $response['correction_file_url'] = $this->gcs->signedUrl($r->correction_file_path, 60);
                    } catch (\Throwable) {
                        $response['correction_file_url'] = null;
                    }
                }
                return $response;
            })->values()
            : [];

        return response()->json([
            'message' => 'Test retrieved successfully',
            'test'    => $testData,
        ]);
    }

    /**
     * Save (or update) answers for a test.
     */
    public function saveAnswers(Request $request, $id)
    {
        $studentId = Auth::id();
        $test      = $this->findAssignedTest($studentId, $id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $validated = $request->validate([
            'answers'               => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:test_questions,test_question_id',
            'answers.*.answer'      => 'required|string',
        ]);

        $submission = TestSubmission::firstOrCreate(
            ['test_id' => $id, 'student_id' => $studentId],
            ['status' => 'in_progress']
        );

        if ($submission->status === 'submitted') {
            return response()->json(['message' => 'Test already submitted'], 409);
        }

        foreach ($validated['answers'] as $item) {
            TestQuestionResponse::updateOrCreate(
                [
                    'submission_id'    => $submission->id,
                    'related_question' => $item['question_id'],
                ],
                [
                    'related_student' => $studentId,
                    'answer'          => $item['answer'],
                ]
            );
        }

        return response()->json([
            'message'    => 'Answers saved successfully',
            'submission' => $submission->fresh()->load('responses'),
        ]);
    }

    /**
     * Submit the test — marks status as 'submitted'.
     */
    public function submit($id)
    {
        $studentId = Auth::id();
        $test      = $this->findAssignedTest($studentId, $id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submission = TestSubmission::where('test_id', $id)
            ->where('student_id', $studentId)
            ->first();

        if (!$submission) {
            return response()->json(['message' => 'No answers saved yet. Save answers before submitting.'], 422);
        }

        if ($submission->status === 'submitted') {
            return response()->json(['message' => 'Test already submitted'], 409);
        }

        $submittedAt = now();

        $submission->update([
            'status'       => 'submitted',
            'submitted_at' => $submittedAt,
        ]);

        $newAchievements = $this->achievements->recordSubmission(
            $studentId,
            $submittedAt,
            'test',
            (int) $id
        );

        return response()->json([
            'message'          => 'Test submitted successfully',
            'submission'       => $submission->fresh(),
            'new_achievements' => $newAchievements,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function studentGroupIds(int $studentId): array
    {
        return Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();
    }

    private function findAssignedTest(int $studentId, $testId): ?Test
    {
        $groupIds = $this->studentGroupIds($studentId);

        return Test::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', $gid);
            }
        })->find($testId);
    }

    private function resolveSignedUrls(Test $test): array
    {
        $materialIds = [];
        foreach ($test->sections as $section) {
            foreach ((array) $section->instruction_files as $id) {
                $materialIds[] = $id;
            }
            if ($section->audio_material_id) {
                $materialIds[] = $section->audio_material_id;
            }
            foreach ($section->questions as $question) {
                foreach ((array) $question->instruction_files as $id) {
                    $materialIds[] = $id;
                }
            }
        }

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

        $data = $test->toArray();
        foreach ($data['sections'] as &$sectionData) {
            $sectionData['instruction_file_urls'] = array_map(
                fn ($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null],
                (array) ($sectionData['instruction_files'] ?? [])
            );
            if (!empty($sectionData['audio_material_id'])) {
                $sectionData['audio_url_signed'] = $signedUrls[$sectionData['audio_material_id']] ?? null;
            }
            foreach ($sectionData['questions'] as &$questionData) {
                $questionData['instruction_file_urls'] = array_map(
                    fn ($id) => ['material_id' => $id, 'url' => $signedUrls[$id] ?? null],
                    (array) ($questionData['instruction_files'] ?? [])
                );
            }
            unset($questionData);
        }
        unset($sectionData);

        return $data;
    }
}
