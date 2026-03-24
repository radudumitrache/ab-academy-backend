<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\QuestionResponse;
use App\Services\GcsService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeworkSubmissionController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all submitted submissions for a homework the teacher owns.
     */
    public function index($homeworkId)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($homeworkId);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submissions = HomeworkSubmission::with(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails'])
            ->where('homework_id', $homeworkId)
            ->where('status', 'submitted')
            ->get()
            ->map(fn($s) => $this->formatSubmission($s));

        return response()->json([
            'message'     => 'Submissions retrieved successfully',
            'count'       => $submissions->count(),
            'submissions' => $submissions,
        ]);
    }

    /**
     * Get a single submission with all responses.
     */
    public function show($homeworkId, $submissionId)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($homeworkId);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::with(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails'])
            ->where('homework_id', $homeworkId)
            ->find($submissionId);

        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        return response()->json([
            'message'    => 'Submission retrieved successfully',
            'submission' => $this->formatSubmission($submission),
        ]);
    }

    /**
     * Save a grade and/or observation on a submitted homework.
     */
    public function grade(Request $request, $homeworkId, $submissionId)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($homeworkId);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::where('homework_id', $homeworkId)->find($submissionId);

        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        if ($submission->status !== 'submitted') {
            return response()->json(['message' => 'Cannot grade a submission that has not been submitted'], 422);
        }

        $validator = Validator::make($request->all(), [
            'grade'       => 'nullable|string|max:50',
            'observation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $submission->update($validator->validated());

        $fresh = $submission->fresh(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails']);

        // Notify the student that their homework has been graded
        NotificationService::notify(
            $submission->student_id,
            "Your homework '{$homework->homework_title}' has been graded.",
            'Teacher',
            'Homework'
        );

        return response()->json([
            'message'    => 'Submission graded successfully',
            'submission' => $this->formatSubmission($fresh),
        ]);
    }

    /**
     * Grade individual question responses within a submission.
     *
     * Accepts multipart/form-data:
     *   - responses: JSON string of [{ response_id, grade, observation }]
     *   - files[{response_id}]: optional correction file per response
     *
     * Correction files are stored at:
     *   teachers/{username}/private/corrections/{submissionId}_{responseId}.{ext}
     */
    public function gradeResponses(Request $request, $homeworkId, $submissionId)
    {
        $homework = Homework::where('homework_teacher', Auth::id())->find($homeworkId);

        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $submission = HomeworkSubmission::where('homework_id', $homeworkId)->find($submissionId);

        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        if ($submission->status !== 'submitted') {
            return response()->json(['message' => 'Cannot grade a submission that has not been submitted'], 422);
        }

        // responses can be sent as:
        // - a plain array (JSON body: {"responses": [...]})
        // - a JSON-encoded string (multipart form: responses="[...]")
        // Laravel (Symfony) does not parse multipart/form-data for PATCH requests.
        // Parse the raw body manually to extract form fields and files.
        $parsedBody   = [];
        $parsedFiles  = [];
        $contentType  = $request->header('Content-Type', '');

        if (str_contains($contentType, 'multipart/form-data')) {
            preg_match('/boundary=("?)(.+?)\1\s*$/i', $contentType, $matches);
            $boundary = $matches[2] ?? '';
            if ($boundary) {
                // Read raw body from php://input since getContent() may be empty
                // after Symfony has already consumed the stream.
                $raw = $request->getContent();
                if ($raw === '') {
                    $raw = file_get_contents('php://input');
                }

                $delimiter = "\r\n--" . $boundary;
                // Find the first part boundary (may start without leading \r\n)
                $start = strpos($raw, '--' . $boundary);
                if ($start !== false) {
                    $pos = $start;
                    while (true) {
                        // Move past the boundary line (boundary + optional \r\n)
                        $lineEnd = strpos($raw, "\r\n", $pos);
                        if ($lineEnd === false) break;
                        $boundaryLine = substr($raw, $pos, $lineEnd - $pos);
                        // Check for closing boundary (ends with --)
                        if (str_ends_with(trim($boundaryLine), '--')) break;

                        $partStart = $lineEnd + 2; // skip \r\n after boundary
                        // Find the next boundary
                        $nextBoundary = strpos($raw, "\r\n--" . $boundary, $partStart);
                        if ($nextBoundary === false) break;

                        $partRaw = substr($raw, $partStart, $nextBoundary - $partStart);

                        // Split headers from body on first blank line
                        $headerEnd = strpos($partRaw, "\r\n\r\n");
                        if ($headerEnd === false) {
                            $pos = $nextBoundary + 2;
                            continue;
                        }
                        $partHeaders = substr($partRaw, 0, $headerEnd);
                        $partBody    = substr($partRaw, $headerEnd + 4);

                        preg_match('/name="([^"]+)"/i', $partHeaders, $nm);
                        $fieldName = $nm[1] ?? null;

                        if ($fieldName) {
                            if (preg_match('/filename="([^"]*)"/i', $partHeaders, $fn)) {
                                preg_match('/Content-Type:\s*(\S+)/i', $partHeaders, $ct);
                                $parsedFiles[$fieldName] = [
                                    'filename'    => $fn[1],
                                    'content'     => $partBody,
                                    'contentType' => $ct[1] ?? 'application/octet-stream',
                                ];
                            } else {
                                $parsedBody[$fieldName] = $partBody;
                            }
                        }

                        $pos = $nextBoundary + 2; // move to \r\n before next --boundary
                    }
                }
            }
        }

        $rawResponses = $request->input('responses')
            ?? ($parsedBody['responses'] ?? null);

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
                'files'                   => 'sometimes|array',
                'files.*'                 => 'file|max:51200',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $teacher           = Auth::user();
        $correctionsFolder = "teachers/{$teacher->username}/private/corrections";

        foreach ($responsesInput as $item) {
            $responseId = (int) $item['response_id'];

            $response = QuestionResponse::where('submission_id', $submissionId)
                ->where('response_id', $responseId)
                ->first();

            if (!$response) {
                return response()->json([
                    'message' => "Response {$responseId} not found in this submission",
                ], 404);
            }

            $updates = [
                'grade'       => array_key_exists('grade', $item) ? $item['grade'] : $response->grade,
                'observation' => array_key_exists('observation', $item) ? $item['observation'] : $response->observation,
            ];

            // Handle optional correction file for this response.
            // Laravel/Symfony does not parse multipart files on PATCH requests,
            // so we check both the standard request files and our manually parsed files.
            $fileKey     = "files.{$responseId}";
            $parsedKey   = "files[{$responseId}]";
            $fileContent = null;
            $fileExt     = null;

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

                // Delete old correction file if present
                if ($response->correction_file_path) {
                    try {
                        $this->gcs->delete($response->correction_file_path);
                    } catch (\Throwable) {}
                }

                $this->gcs->createFolder($correctionsFolder);
                $this->gcs->uploadContent($fileContent, $path);
                $updates['correction_file_path'] = $path;
            }

            $response->update($updates);
        }

        $fresh = $submission->fresh(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails']);

        // Notify the student that their homework responses have been graded
        NotificationService::notify(
            $submission->student_id,
            "Your homework '{$homework->homework_title}' has been graded.",
            'Teacher',
            'Homework'
        );

        return response()->json([
            'message'    => 'Responses graded successfully',
            'submission' => $this->formatSubmission($fresh),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Format a submission, resolving multiple_choice answer index to variant text.
     */
    private function formatSubmission(HomeworkSubmission $submission): array
    {
        $data = $submission->toArray();

        $data['responses'] = collect($submission->responses)->map(function ($response) {
            $row = $response->toArray();
            $q   = $response->question;

            $row['answer_text']         = null;
            $row['correct_answer']      = null;
            $row['file_url']            = $response->file_path
                ? $this->gcs->signedUrl($response->file_path, 60)
                : null;
            $row['correction_file_url'] = $response->correction_file_path
                ? $this->gcs->signedUrl($response->correction_file_path, 60)
                : null;

            if (!$q) return $row;

            switch ($q->question_type) {
                case 'multiple_choice':
                    $variants = $q->multipleChoiceDetails?->variants ?? [];
                    $correct  = $q->multipleChoiceDetails?->correct_variant;
                    if ($response->answer !== null) {
                        $row['answer_text'] = $variants[(int) $response->answer] ?? $response->answer;
                    }
                    if ($correct !== null) {
                        $row['correct_answer'] = $variants[(int) $correct] ?? null;
                    }
                    break;

                case 'gap_fill':
                    $row['correct_answer'] = $q->gapFillDetails?->correct_answers;
                    break;

                case 'text_completion':
                    $row['correct_answer'] = $q->textCompletionDetails?->correct_answers;
                    break;

                case 'correlation':
                    $row['correct_answer'] = $q->correlationDetails?->correct_pairs;
                    break;

                case 'correct':
                    $row['correct_answer'] = $q->correctDetails?->sample_answer;
                    break;

                case 'word_formation':
                    $row['correct_answer'] = $q->wordFormationDetails?->sample_answer;
                    break;

                case 'rephrase':
                    $row['correct_answer'] = $q->rephraseDetails?->sample_answer;
                    break;

                case 'replace':
                    $row['correct_answer'] = $q->replaceDetails?->sample_answer;
                    break;

                case 'word_derivation':
                    $row['correct_answer'] = $q->wordDerivationDetails?->sample_answer;
                    break;
            }

            return $row;
        })->values()->toArray();

        return $data;
    }
}
