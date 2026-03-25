<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Material;
use App\Models\QuestionResponse;
use App\Services\AchievementService;
use App\Services\GcsService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HomeworkController extends Controller
{
    public function __construct(
        private GcsService $gcs,
        private AchievementService $achievements,
    ) {}

    /**
     * List all homework assigned to the authenticated student.
     */
    public function index()
    {
        $studentId = Auth::id();

        $groupIds = \App\Models\Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
            ->pluck('group_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        // Filter in PHP to avoid MySQL JSON type-coercion issues and to include past homework.
        $homework = Homework::orderByDesc('due_date')->get()->filter(function ($hw) use ($studentId, $groupIds) {
            $people = array_map('intval', (array) $hw->people_assigned);
            $groups = array_map('intval', (array) $hw->groups_assigned);
            if (in_array($studentId, $people, true)) return true;
            foreach ($groupIds as $gid) {
                if (in_array($gid, $groups, true)) return true;
            }
            return false;
        });

        $submissionMap = HomeworkSubmission::where('student_id', $studentId)
            ->whereIn('homework_id', $homework->pluck('id'))
            ->get()
            ->keyBy('homework_id');

        $result = $homework->map(function ($hw) use ($submissionMap) {
            $data = $hw->toArray();
            $sub  = $submissionMap->get($hw->id);
            $data['submission_status'] = $sub ? $sub->status : 'not_started';
            $data['submitted_at']      = $sub ? $sub->submitted_at : null;
            $data['grade']             = $sub ? $sub->grade : null;
            $data['observation']       = $sub ? $sub->observation : null;
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
        $homeworkData['grade']             = $sub ? $sub->grade : null;
        $homeworkData['observation']       = $sub ? $sub->observation : null;

        if ($sub) {
            $homeworkData['responses'] = $sub->responses->map(function ($r) {
                $response = [
                    'question_id'         => $r->related_question,
                    'answer'              => $r->answer,
                    'file_path'           => $r->file_path,
                    'file_url'            => null,
                    'grade'               => $r->grade,
                    'observation'         => $r->observation,
                    'correction_file_url' => null,
                ];
                if ($r->file_path) {
                    try {
                        $response['file_url'] = $this->gcs->signedUrl($r->file_path, 60);
                    } catch (\Throwable) {
                        $response['file_url'] = null;
                    }
                }
                if ($r->correction_file_path) {
                    try {
                        $response['correction_file_url'] = $this->gcs->signedUrl($r->correction_file_path, 60);
                    } catch (\Throwable) {
                        $response['correction_file_url'] = null;
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
            $hasGradedResponse = $submission->responses()->whereNotNull('grade')->exists();
            if ($hasGradedResponse) {
                return response()->json(['message' => 'Cannot edit answers after grading has started'], 409);
            }
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

        if ($submission->status === 'submitted') {
            $submission->update(['submitted_at' => now()]);
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

        $submission = HomeworkSubmission::firstOrCreate(
            ['homework_id' => $id, 'student_id' => $studentId],
            ['status' => 'in_progress']
        );

        if ($submission->status === 'submitted' && !is_null($submission->grade)) {
            return response()->json(['message' => 'Homework already submitted and graded'], 409);
        }

        $submittedAt = now();

        $submission->update([
            'status'       => 'submitted',
            'submitted_at' => $submittedAt,
        ]);

        $newAchievements = $this->achievements->recordSubmission(
            $studentId,
            $submittedAt,
            'homework',
            (int) $id
        );

        // Notify the homework's teacher
        if ($homework->homework_teacher) {
            $student = Auth::user();
            NotificationService::notify(
                $homework->homework_teacher,
                "Student {$student->username} submitted homework '{$homework->homework_title}'.",
                'Student',
                'Homework'
            );
        }

        return response()->json([
            'message'          => 'Homework submitted successfully',
            'submission'       => $submission->fresh(),
            'new_achievements' => $newAchievements,
        ]);
    }

    /**
     * Return the student's submission results and teacher feedback for a homework.
     *
     * Only available once the homework has been submitted.
     */
    public function results($id)
    {
        $studentId = Auth::id();
        $homework  = $this->findAssignedHomework($studentId, $id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $sub = HomeworkSubmission::where('homework_id', $id)
            ->where('student_id', $studentId)
            ->with(['responses.question.multipleChoiceDetails',
                    'responses.question.gapFillDetails',
                    'responses.question.textCompletionDetails',
                    'responses.question.correlationDetails',
                    'responses.question.correctDetails',
                    'responses.question.wordFormationDetails',
                    'responses.question.rephraseDetails',
                    'responses.question.replaceDetails',
                    'responses.question.wordDerivationDetails'])
            ->first();

        if (!$sub) {
            return response()->json(['message' => 'No submission found'], 404);
        }

        if ($sub->status !== 'submitted') {
            return response()->json(['message' => 'Homework has not been submitted yet'], 422);
        }

        $responses = $sub->responses->map(function ($r) {
            $q = $r->question;

            $answerText    = null;
            $correctAnswer = null;

            if ($q) {
                switch ($q->question_type) {
                    case 'multiple_choice':
                        $variants = $q->multipleChoiceDetails?->variants ?? [];
                        $correct  = $q->multipleChoiceDetails?->correct_variant;
                        if ($r->answer !== null) {
                            $answerText = $variants[(int) $r->answer] ?? $r->answer;
                        }
                        if ($correct !== null) {
                            $correctAnswer = $variants[(int) $correct] ?? null;
                        }
                        break;
                    case 'gap_fill':
                        $correctAnswer = $q->gapFillDetails?->correct_answers;
                        break;
                    case 'text_completion':
                        $correctAnswer = $q->textCompletionDetails?->correct_answers;
                        break;
                    case 'correlation':
                        $correctAnswer = $q->correlationDetails?->correct_pairs;
                        break;
                    case 'correct':
                        $correctAnswer = $q->correctDetails?->sample_answer;
                        break;
                    case 'word_formation':
                        $correctAnswer = $q->wordFormationDetails?->sample_answer;
                        break;
                    case 'rephrase':
                        $correctAnswer = $q->rephraseDetails?->sample_answer;
                        break;
                    case 'replace':
                        $correctAnswer = $q->replaceDetails?->sample_answer;
                        break;
                    case 'word_derivation':
                        $correctAnswer = $q->wordDerivationDetails?->sample_answer;
                        break;
                }
            }

            $correctionFileUrl = null;
            if ($r->correction_file_path) {
                try {
                    $correctionFileUrl = $this->gcs->signedUrl($r->correction_file_path, 60);
                } catch (\Throwable) {}
            }

            $fileUrl = null;
            if ($r->file_path) {
                try {
                    $fileUrl = $this->gcs->signedUrl($r->file_path, 60);
                } catch (\Throwable) {}
            }

            return [
                'response_id'         => $r->response_id,
                'question_id'         => $r->related_question,
                'question_type'       => $q?->question_type,
                'question_text'       => $q?->question_text,
                'answer'              => $r->answer,
                'answer_text'         => $answerText,
                'file_url'            => $fileUrl,
                'correct_answer'      => $correctAnswer,
                'grade'               => $r->grade,
                'observation'         => $r->observation,
                'correction_file_url' => $correctionFileUrl,
            ];
        })->values();

        return response()->json([
            'message' => 'Results retrieved successfully',
            'results' => [
                'submission_id' => $sub->id,
                'submitted_at'  => $sub->submitted_at,
                'grade'         => $sub->grade,
                'observation'   => $sub->observation,
                'responses'     => $responses,
            ],
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function findAssignedHomework(int $studentId, $homeworkId): ?Homework
    {
        $groupIds = \App\Models\Group::whereHas('students', fn($s) => $s->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();

        // Fetch by ID first, then verify assignment in PHP — avoids any
        // MySQL JSON type-coercion issues with whereJsonContains + find().
        $homework = Homework::find((int) $homeworkId);

        if (!$homework) {
            return null;
        }

        $peopleAssigned = array_map('intval', (array) $homework->people_assigned);
        $groupsAssigned = array_map('intval', (array) $homework->groups_assigned);

        if (in_array($studentId, $peopleAssigned, true)) {
            return $homework;
        }

        foreach ($groupIds as $gid) {
            if (in_array((int) $gid, $groupsAssigned, true)) {
                return $homework;
            }
        }

        return null;
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
