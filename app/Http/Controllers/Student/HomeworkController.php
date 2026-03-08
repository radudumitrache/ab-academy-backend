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
use Illuminate\Support\Str;

class HomeworkController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all homework assigned to the authenticated student.
     */
    public function index()
    {
        $studentId = Auth::id();

        $groupIds = \App\Models\Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();

        $homework = Homework::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', (int) $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', (int) $gid);
            }
        })
        ->orderByDesc('due_date')
        ->get();

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
     * Show a single homework with all sections, questions, and existing responses.
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

        $sub = HomeworkSubmission::where('homework_id', $id)
            ->where('student_id', $studentId)
            ->with('responses')
            ->first();

        $homeworkData['submission_status'] = $sub ? $sub->status : 'not_started';
        $homeworkData['submitted_at']      = $sub ? $sub->submitted_at : null;

        if ($sub) {
            $homeworkData['responses'] = $sub->responses->map(function ($r) {
                $response = [
                    'question_id' => $r->related_question,
                    'answer'      => $r->answer,
                    'file_path'   => $r->file_path,
                    'file_url'    => null,
                ];
                if ($r->file_path) {
                    try {
                        $response['file_url'] = $this->gcs->signedUrl($r->file_path, 60);
                    } catch (\Throwable) {
                        $response['file_url'] = null;
                    }
                }
                return $response;
            })->values();
        } else {
            $homeworkData['responses'] = [];
        }

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'homework' => $homeworkData,
        ]);
    }

    /**
     * Save (or update) answers for a homework.
     *
     * Accepts multipart/form-data with:
     *   - answers[]  (JSON string of [{question_id, answer}] for text answers)
     *   - files[{question_id}]  (uploaded file for a specific question)
     *
     * File path in GCS:
     *   teachers/{teacher_username}/private/submissions/{student_id}_{homework_slug}_{section_slug}_{question_index}.{ext}
     */
    public function saveAnswers(Request $request, $id)
    {
        $studentId = Auth::id();
        $homework  = $this->findAssignedHomework($studentId, $id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $request->validate([
            'answers'   => 'sometimes|array',
            'answers.*.question_id' => 'required_with:answers|integer|exists:questions,question_id',
            'answers.*.answer'      => 'required_with:answers|string',
            'files'     => 'sometimes|array',
            'files.*'   => 'file|max:51200', // 50 MB per file
        ]);

        if (empty($request->answers) && !$request->hasFile('files')) {
            return response()->json(['message' => 'No answers or files provided'], 422);
        }

        $submission = HomeworkSubmission::firstOrCreate(
            ['homework_id' => $id, 'student_id' => $studentId],
            ['status' => 'in_progress']
        );

        if ($submission->status === 'submitted') {
            return response()->json(['message' => 'Homework already submitted'], 409);
        }

        // ── Text answers ──────────────────────────────────────────────────────
        foreach ($request->answers ?? [] as $item) {
            QuestionResponse::updateOrCreate(
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

        // ── File answers ──────────────────────────────────────────────────────
        if ($request->hasFile('files')) {
            // Load teacher username + section info for path building
            $homework->load(['teacher', 'sections.questions']);
            $teacherUsername = $homework->teacher->username ?? 'unknown';

            // Ensure the submissions folder exists
            $submissionsFolder = "teachers/{$teacherUsername}/private/submissions";
            $this->gcs->createFolder($submissionsFolder);

            // Build a lookup: question_id → [section_slug, question_index]
            $questionMeta = [];
            foreach ($homework->sections as $section) {
                $sectionSlug = Str::slug($section->title ?? $section->section_type);
                foreach ($section->questions as $index => $question) {
                    $questionMeta[$question->question_id] = [
                        'section_slug'   => $sectionSlug,
                        'question_index' => $index + 1,
                    ];
                }
            }

            $homeworkSlug = Str::slug($homework->homework_title);

            foreach ($request->file('files') as $questionId => $file) {
                $questionId = (int) $questionId;

                // Validate the question belongs to this homework
                if (!isset($questionMeta[$questionId])) {
                    continue;
                }

                $meta      = $questionMeta[$questionId];
                $ext       = $file->getClientOriginalExtension();
                $filename  = "{$studentId}_{$homeworkSlug}_{$meta['section_slug']}_{$meta['question_index']}.{$ext}";
                $gcsPath   = "{$submissionsFolder}/{$filename}";

                // Delete old file if one already exists for this question
                $existing = QuestionResponse::where('submission_id', $submission->id)
                    ->where('related_question', $questionId)
                    ->first();

                if ($existing && $existing->file_path) {
                    try {
                        $this->gcs->delete($existing->file_path);
                    } catch (\Throwable) {
                        // non-fatal
                    }
                }

                $this->gcs->upload($file, $gcsPath);

                QuestionResponse::updateOrCreate(
                    [
                        'submission_id'    => $submission->id,
                        'related_question' => $questionId,
                    ],
                    [
                        'related_student' => $studentId,
                        'answer'          => null,
                        'file_path'       => $gcsPath,
                    ]
                );
            }
        }

        return response()->json([
            'message'    => 'Answers saved successfully',
            'submission' => $submission->fresh()->load('responses'),
        ]);
    }

    /**
     * Submit the homework — marks status as 'submitted'.
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
            $q->whereJsonContains('people_assigned', (int) $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', (int) $gid);
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
