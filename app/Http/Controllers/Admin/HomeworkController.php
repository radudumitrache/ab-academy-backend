<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Homework;
use App\Models\HomeworkSection;
use App\Models\HomeworkSubmission;
use App\Models\Material;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Services\GcsService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HomeworkController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    // ── Homework CRUD ─────────────────────────────────────────────────────────

    /**
     * List all homework across all teachers.
     */
    public function index()
    {
        $homework = Homework::with('teacher:id,username')
            ->withCount('allQuestions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'count'    => $homework->count(),
            'homework' => $homework,
        ]);
    }

    /**
     * Show a single homework with all sections, questions and signed URLs.
     */
    public function show($id)
    {
        $homework = Homework::with('teacher:id,username')->find($id);

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

        return response()->json([
            'message'  => 'Homework retrieved successfully',
            'homework' => $homeworkData,
        ]);
    }

    /**
     * Create homework. Admin sets the teacher explicitly via homework_teacher field.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'homework_title'       => 'required|string|max:255',
            'homework_description' => 'nullable|string',
            'due_date'             => 'required|date_format:Y-m-d',
            'homework_teacher'     => 'nullable|integer|exists:users,id',
            'people_assigned'      => 'nullable|array',
            'people_assigned.*'    => 'integer|exists:users,id',
            'groups_assigned'      => 'nullable|array',
            'groups_assigned.*'    => 'integer|exists:groups,group_id',
            'status'               => ['sometimes', Rule::in(Homework::STATUSES)],
        ]);

        $validated['homework_teacher'] = $validated['homework_teacher'] ?? Auth::id();
        $validated['date_created']     = now();

        $homework = Homework::create($validated);

        DatabaseLog::logAction('create', Homework::class, $homework->id, "Homework '{$homework->homework_title}' created");

        return response()->json([
            'message'  => 'Homework created successfully',
            'homework' => $homework,
        ], 201);
    }

    /**
     * Update any homework (no ownership restriction).
     */
    public function update(Request $request, $id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'homework_title'       => 'sometimes|string|max:255',
            'homework_description' => 'nullable|string',
            'due_date'             => 'sometimes|date_format:Y-m-d',
            'people_assigned'      => 'nullable|array',
            'people_assigned.*'    => 'integer|exists:users,id',
            'groups_assigned'      => 'nullable|array',
            'groups_assigned.*'    => 'integer|exists:groups,group_id',
            'status'               => ['sometimes', Rule::in(Homework::STATUSES)],
        ]);

        $homework->update($validated);

        DatabaseLog::logAction('update', Homework::class, $homework->id, "Homework '{$homework->homework_title}' updated");

        return response()->json([
            'message'  => 'Homework updated successfully',
            'homework' => $homework->fresh(),
        ]);
    }

    /**
     * Delete any homework. Sections and questions cascade-delete.
     */
    public function destroy($id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $homeworkTitle = $homework->homework_title;
        $homeworkId = $homework->id;
        $homework->delete();

        DatabaseLog::logAction('delete', Homework::class, $homeworkId, "Homework '{$homeworkTitle}' deleted");

        return response()->json(['message' => 'Homework deleted successfully']);
    }

    /**
     * Assign (or replace) students/groups on any homework — no ownership restriction.
     */
    public function assignStudents(Request $request, $id)
    {
        $homework = Homework::find($id);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'people_assigned'   => 'nullable|array',
            'people_assigned.*' => 'integer|exists:users,id',
            'groups_assigned'   => 'nullable|array',
            'groups_assigned.*' => 'integer|exists:groups,group_id',
        ]);

        $homework->update([
            'people_assigned' => $validated['people_assigned'] ?? [],
            'groups_assigned' => $validated['groups_assigned'] ?? [],
        ]);

        DatabaseLog::logAction('update', Homework::class, $homework->id, "Students assigned to homework '{$homework->homework_title}'");

        return response()->json([
            'message'  => 'Students assigned successfully',
            'homework' => $homework->fresh(),
        ]);
    }

    // ── Sections ──────────────────────────────────────────────────────────────

    public function sectionIndex($homeworkId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $sections = HomeworkSection::where('homework_id', $homeworkId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return response()->json([
            'message'  => 'Sections retrieved successfully',
            'sections' => $sections,
        ]);
    }

    public function sectionStore(Request $request, $homeworkId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(HomeworkSection::TYPES)],
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

        $section = HomeworkSection::create([
            'homework_id'       => $homeworkId,
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

        DatabaseLog::logAction('create', HomeworkSection::class, $section->id, "Section created for homework #{$homeworkId}");

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    public function sectionUpdate(Request $request, $homeworkId, $sectionId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
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

        DatabaseLog::logAction('update', HomeworkSection::class, $section->id, "Section #{$sectionId} updated for homework #{$homeworkId}");

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    public function sectionDestroy($homeworkId, $sectionId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        DatabaseLog::logAction('delete', HomeworkSection::class, $sectionId, "Section #{$sectionId} deleted from homework #{$homeworkId}");

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // ── Submissions (view only) ───────────────────────────────────────────────

    /**
     * Get a single submission with all responses and full question details.
     */
    public function submissionShow($homeworkId, $submissionId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::with([
            'student:id,username,email',
            'responses.question.multipleChoiceDetails',
            'responses.question.gapFillDetails',
            'responses.question.textCompletionDetails',
            'responses.question.correlationDetails',
            'responses.question.correctDetails',
            'responses.question.wordFormationDetails',
            'responses.question.rephraseDetails',
            'responses.question.replaceDetails',
            'responses.question.wordDerivationDetails',
            'responses.question.writingQuestionDetails',
            'responses.question.speakingQuestionDetails',
            'responses.question.readingQuestionDetails',
            'responses.question.mixedQuestionDetails',
        ])
            ->where('homework_id', $homeworkId)
            ->find($submissionId);

        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        $submission->responses->each(function ($response) {
            try {
                $response->file_url = $response->file_path
                    ? $this->gcs->signedUrl($response->file_path, 60)
                    : null;
            } catch (\Throwable) {
                $response->file_url = null;
            }
            try {
                $response->correction_file_url = $response->correction_file_path
                    ? $this->gcs->signedUrl($response->correction_file_path, 60)
                    : null;
            } catch (\Throwable) {
                $response->correction_file_url = null;
            }
        });

        return response()->json([
            'message'    => 'Submission retrieved successfully',
            'submission' => $submission,
        ]);
    }

    /**
     * List all student submissions for a homework.
     * File responses include a signed GCS download URL.
     */
    public function submissions($homeworkId)
    {
        if (!Homework::find($homeworkId)) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submissions = HomeworkSubmission::where('homework_id', $homeworkId)
            ->with(['student:id,username,email', 'responses'])
            ->get();

        // Attach signed URLs for any file-based responses
        $submissions->each(function ($submission) {
            $submission->responses->each(function ($response) {
                try {
                    $response->file_url = $response->file_path
                        ? $this->gcs->signedUrl($response->file_path, 60)
                        : null;
                } catch (\Throwable) {
                    $response->file_url = null;
                }
                try {
                    $response->correction_file_url = $response->correction_file_path
                        ? $this->gcs->signedUrl($response->correction_file_path, 60)
                        : null;
                } catch (\Throwable) {
                    $response->correction_file_url = null;
                }
            });
        });

        return response()->json([
            'message'     => 'Submissions retrieved successfully',
            'count'       => $submissions->count(),
            'submissions' => $submissions,
        ]);
    }

    /**
     * Grade a submission (overall score + observation).
     */
    public function submissionGrade(Request $request, $homeworkId, $submissionId)
    {
        $homework = Homework::find($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::where('homework_id', $homeworkId)->find($submissionId);
        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'grade'         => 'nullable|string|max:50',
            'observation'   => 'nullable|string',
            'ai_correction' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $submission->update($validator->validated());

        NotificationService::notify(
            $submission->student_id,
            "Your homework '{$homework->homework_title}' has been graded.",
            'Teacher',
            'Homework'
        );

        return response()->json([
            'message'    => 'Submission graded successfully',
            'submission' => $submission->fresh(['student:id,username,email']),
        ]);
    }

    /**
     * Grade individual question responses within a submission.
     * Accepts multipart/form-data with a JSON-encoded `responses` field and optional `files[{response_id}]`.
     */
    public function submissionGradeResponses(Request $request, $homeworkId, $submissionId)
    {
        $homework = Homework::find($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::where('homework_id', $homeworkId)->find($submissionId);
        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        // Parse multipart body manually (same approach as teacher controller — Symfony
        // does not parse multipart/form-data for PATCH requests automatically).
        $parsedBody  = [];
        $parsedFiles = [];
        $contentType = $request->header('Content-Type', '');

        if (str_contains($contentType, 'multipart/form-data')) {
            preg_match('/boundary=("?)(.+?)\1\s*$/i', $contentType, $matches);
            $boundary = $matches[2] ?? '';
            if ($boundary) {
                $raw = $request->getContent();
                if ($raw === '') {
                    $raw = file_get_contents('php://input');
                }
                $start = strpos($raw, '--' . $boundary);
                if ($start !== false) {
                    $pos = $start;
                    while (true) {
                        $lineEnd = strpos($raw, "\r\n", $pos);
                        if ($lineEnd === false) break;
                        $boundaryLine = substr($raw, $pos, $lineEnd - $pos);
                        if (str_ends_with(trim($boundaryLine), '--')) break;
                        $partStart = $lineEnd + 2;
                        $nextBoundary = strpos($raw, "\r\n--" . $boundary, $partStart);
                        if ($nextBoundary === false) break;
                        $partRaw   = substr($raw, $partStart, $nextBoundary - $partStart);
                        $headerEnd = strpos($partRaw, "\r\n\r\n");
                        if ($headerEnd === false) { $pos = $nextBoundary + 2; continue; }
                        $partHeaders = substr($partRaw, 0, $headerEnd);
                        $partBody    = substr($partRaw, $headerEnd + 4);
                        preg_match('/name="([^"]+)"/i', $partHeaders, $nm);
                        $fieldName = $nm[1] ?? null;
                        if ($fieldName) {
                            if (preg_match('/filename="([^"]*)"/i', $partHeaders, $fn)) {
                                preg_match('/Content-Type:\s*(\S+)/i', $partHeaders, $ct);
                                $parsedFiles[$fieldName] = ['filename' => $fn[1], 'content' => $partBody, 'contentType' => $ct[1] ?? 'application/octet-stream'];
                            } else {
                                $parsedBody[$fieldName] = $partBody;
                            }
                        }
                        $pos = $nextBoundary + 2;
                    }
                }
            }
        }

        $rawResponses = $request->input('responses') ?? ($parsedBody['responses'] ?? null);
        if (is_string($rawResponses)) {
            $responsesInput = json_decode($rawResponses, true) ?? null;
        } elseif (is_array($rawResponses)) {
            $responsesInput = $rawResponses;
        } else {
            $responsesInput = $request->json('responses');
        }

        $validator = Validator::make(
            array_merge($request->all(), ['responses' => $responsesInput]),
            [
                'responses'               => 'required|array|min:1',
                'responses.*.response_id' => 'required|integer',
                'responses.*.grade'       => 'nullable|string|max:50',
                'responses.*.observation' => 'nullable|string',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $teacher           = Auth::user();
        $correctionsFolder = "teachers/{$teacher->username}/private/corrections";

        foreach ($responsesInput as $item) {
            $responseId = (int) $item['response_id'];
            $response   = QuestionResponse::where('submission_id', $submissionId)->where('response_id', $responseId)->first();
            if (!$response) {
                return response()->json(['message' => "Response {$responseId} not found in this submission"], 404);
            }

            $updates   = [
                'grade'       => array_key_exists('grade', $item) ? $item['grade'] : $response->grade,
                'observation' => array_key_exists('observation', $item) ? $item['observation'] : $response->observation,
            ];

            $fileKey     = "files.{$responseId}";
            $parsedKey   = "files[{$responseId}]";
            $fileContent = null;
            $fileExt     = null;
            $parsed      = null;

            if ($request->hasFile($fileKey)) {
                $file        = $request->file($fileKey);
                $fileExt     = $file->getClientOriginalExtension();
                $fileContent = file_get_contents($file->getRealPath());
            } elseif (isset($parsedFiles[$parsedKey]) && $parsedFiles[$parsedKey]['content'] !== '') {
                $parsed      = $parsedFiles[$parsedKey];
                $fileExt     = pathinfo($parsed['filename'], PATHINFO_EXTENSION) ?: 'bin';
                $fileContent = $parsed['content'];
            }

            if ($fileContent !== null) {
                $path = "{$correctionsFolder}/{$submissionId}_{$responseId}.{$fileExt}";
                if ($response->correction_file_path) {
                    try { $this->gcs->delete($response->correction_file_path); } catch (\Throwable) {}
                    Material::where('gcs_path', $response->correction_file_path)->delete();
                }
                $this->gcs->createFolder($correctionsFolder);
                $this->gcs->uploadContent($fileContent, $path);
                $updates['correction_file_path'] = $path;

                $originalName = isset($parsed) ? $parsed['filename']
                    : ($request->hasFile($fileKey) ? $request->file($fileKey)->getClientOriginalName() : basename($path));

                Material::create([
                    'material_name'  => $originalName,
                    'file_type'      => isset($parsed) ? ($parsed['contentType'] ?? 'application/octet-stream') : ($request->hasFile($fileKey) ? $request->file($fileKey)->getClientMimeType() : 'application/octet-stream'),
                    'date_created'   => now(),
                    'authors'        => [$teacher->id],
                    'allowed_users'  => [],
                    'allowed_groups' => [],
                    'gcs_path'       => $path,
                    'uploader_id'    => $teacher->id,
                    'folder'         => 'private/corrections',
                ]);
            }

            $response->update($updates);
        }

        NotificationService::notify(
            $submission->student_id,
            "Your homework '{$homework->homework_title}' has been graded.",
            'Teacher',
            'Homework'
        );

        return response()->json([
            'message'    => 'Responses graded successfully',
            'submission' => $submission->fresh(['student:id,username,email']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
