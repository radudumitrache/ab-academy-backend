<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\TestQuestionResponse;
use App\Services\GcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TestSubmissionController extends Controller
{
    public function __construct(private GcsService $gcs) {}

    /**
     * List all submitted submissions for a test the teacher owns.
     */
    public function index($testId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submissions = TestSubmission::with(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails'])
            ->where('test_id', $testId)
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
    public function show($testId, $submissionId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submission = TestSubmission::with(['student:id,username,email', 'responses.question.multipleChoiceDetails',
                'responses.question.gapFillDetails',
                'responses.question.textCompletionDetails',
                'responses.question.correlationDetails',
                'responses.question.correctDetails',
                'responses.question.wordFormationDetails',
                'responses.question.rephraseDetails',
                'responses.question.replaceDetails',
                'responses.question.wordDerivationDetails'])
            ->where('test_id', $testId)
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
     * Save a grade and/or observation on a submitted test.
     */
    public function grade(Request $request, $testId, $submissionId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submission = TestSubmission::where('test_id', $testId)->find($submissionId);

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

        return response()->json([
            'message'    => 'Submission graded successfully',
            'submission' => $this->formatSubmission($fresh),
        ]);
    }

    /**
     * Grade individual question responses within a test submission.
     *
     * Accepts multipart/form-data:
     *   - responses: JSON string of [{ response_id, grade, observation }]
     *   - files[{response_id}]: optional correction file per response
     *
     * Correction files are stored at:
     *   teachers/{username}/private/corrections/{submissionId}_{responseId}.{ext}
     */
    public function gradeResponses(Request $request, $testId, $submissionId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submission = TestSubmission::where('test_id', $testId)->find($submissionId);

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
        $contentType  = $request->header('Content-Type', '');

        if (str_contains($contentType, 'multipart/form-data')) {
            preg_match('/boundary=(.+)$/', $contentType, $matches);
            $boundary = $matches[1] ?? null;
            if ($boundary) {
                $raw  = $request->getContent();
                $parts = array_slice(explode('--' . $boundary, $raw), 1, -1);
                foreach ($parts as $part) {
                    if (trim($part) === '--') continue;
                    [$headers, $body] = explode("\r\n\r\n", $part, 2);
                    $body = rtrim($body, "\r\n");
                    if (preg_match('/name="([^"]+)"/', $headers, $nm)) {
                        $parsedBody[$nm[1]] = $body;
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

            $response = TestQuestionResponse::where('submission_id', $submissionId)
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

            // Handle optional correction file for this response
            if ($request->hasFile("files.{$responseId}")) {
                $file = $request->file("files.{$responseId}");
                $ext  = $file->getClientOriginalExtension();
                $path = "{$correctionsFolder}/{$submissionId}_{$responseId}.{$ext}";

                // Delete old correction file if present
                if ($response->correction_file_path) {
                    try {
                        $this->gcs->delete($response->correction_file_path);
                    } catch (\Throwable) {}
                }

                $this->gcs->createFolder($correctionsFolder);
                $this->gcs->upload($file, $path);
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

        return response()->json([
            'message'    => 'Responses graded successfully',
            'submission' => $this->formatSubmission($fresh),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Format a submission, resolving multiple_choice answer index to variant text.
     */
    private function formatSubmission(TestSubmission $submission): array
    {
        $data = $submission->toArray();

        $data['responses'] = collect($submission->responses)->map(function ($response) {
            $row = $response->toArray();
            $q   = $response->question;

            $row['answer_text']    = null;
            $row['correct_answer'] = null;

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
