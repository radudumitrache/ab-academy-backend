<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Material;
use App\Models\QuestionResponse;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeworkController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all homework assigned to the authenticated student.
     */
    public function index()
    {
        $studentId = Auth::id();

        $homework = Homework::where(function ($q) use ($studentId) {
            $q->whereJsonContains('people_assigned', $studentId)
              ->orWhereHas('sections', function () {
                  // always false fallback — groups_assigned checked below
              });
        })
        ->orWhere(function ($q) use ($studentId) {
            // Find group IDs the student belongs to
            $groupIds = \App\Models\Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
                ->pluck('group_id')
                ->toArray();

            if (!empty($groupIds)) {
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('groups_assigned', $gid);
                }
            }
        })
        ->orderByDesc('due_date')
        ->get();

        // Attach submission status for this student
        $submissionMap = HomeworkSubmission::where('student_id', $studentId)
            ->whereIn('homework_id', $homework->pluck('id'))
            ->get()
            ->keyBy('homework_id');

        $result = $homework->map(function ($hw) use ($submissionMap) {
            $data = $hw->toArray();
            $sub  = $submissionMap->get($hw->id);
            $data['submission_status'] = $sub ? $sub->status : 'not_started';
            $data['submitted_at']      = $sub ? $sub->submitted_at : null;
            return $data;
        });

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'count'    => $result->count(),
            'homework' => $result,
        ]);
    }

    /**
     * Show a single homework with all sections and questions.
     * The student must be assigned (directly or via group).
     */
    public function show($id)
    {
        $studentId = Auth::id();
        $homework  = $this->findAssignedHomework($studentId, $id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homework->load([
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

        $homeworkData = $this->resolveSignedUrls($homework);

        // Attach submission state
        $sub = HomeworkSubmission::where('homework_id', $id)
            ->where('student_id', $studentId)
            ->with('responses')
            ->first();

        $homeworkData['submission_status'] = $sub ? $sub->status : 'not_started';
        $homeworkData['submitted_at']      = $sub ? $sub->submitted_at : null;

        // Map existing responses by question_id so the frontend can pre-populate
        if ($sub) {
            $homeworkData['responses'] = $sub->responses->keyBy('related_question')
                ->map(fn($r) => ['question_id' => $r->related_question, 'answer' => $r->answer])
                ->values();
        } else {
            $homeworkData['responses'] = [];
        }

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'homework' => $homeworkData,
        ]);
    }

    /**
     * Save (or update) answers for a homework — creates/updates the submission record.
     * Can be called multiple times before final submission.
     */
    public function saveAnswers(Request $request, $id)
    {
        $studentId = Auth::id();
        $homework  = $this->findAssignedHomework($studentId, $id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'answers'              => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,question_id',
            'answers.*.answer'     => 'required|string',
        ]);

        $submission = HomeworkSubmission::firstOrCreate(
            ['homework_id' => $id, 'student_id' => $studentId],
            ['status' => 'in_progress']
        );

        if ($submission->status === 'submitted') {
            return response()->json(['message' => 'Homework already submitted'], 409);
        }

        foreach ($validated['answers'] as $item) {
            QuestionResponse::updateOrCreate(
                [
                    'submission_id'   => $submission->id,
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
     * Submit the homework — marks status as 'submitted' and records submitted_at.
     */
    public function submit($id)
    {
        $studentId = Auth::id();
        $homework  = $this->findAssignedHomework($studentId, $id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::where('homework_id', $id)
            ->where('student_id', $studentId)
            ->first();

        if (!$submission) {
            return response()->json(['message' => 'No answers saved yet. Save answers before submitting.'], 422);
        }

        if ($submission->status === 'submitted') {
            return response()->json(['message' => 'Homework already submitted'], 409);
        }

        $submission->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message'    => 'Homework submitted successfully',
            'submission' => $submission->fresh(),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function findAssignedHomework(int $studentId, $homeworkId): ?Homework
    {
        $groupIds = \App\Models\Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();

        $query = Homework::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', $gid);
            }
        });

        return $query->find($homeworkId);
    }

    private function resolveSignedUrls(Homework $homework): array
    {
        $materialIds = [];
        foreach ($homework->sections as $section) {
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

        $data = $homework->toArray();
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
