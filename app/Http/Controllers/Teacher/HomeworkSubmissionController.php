<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\QuestionResponse;
use App\Services\GcsService;
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

        $submissions = HomeworkSubmission::with(['student:id,username,email', 'responses.question'])
            ->where('homework_id', $homeworkId)
            ->where('status', 'submitted')
            ->get();

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

        $submission = HomeworkSubmission::with(['student:id,username,email', 'responses.question'])
            ->where('homework_id', $homeworkId)
            ->find($submissionId);

        if (!$submission) {
            return response()->json(['message' => 'Submission not found'], 404);
        }

        return response()->json([
            'message'    => 'Submission retrieved successfully',
            'submission' => $submission,
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

        return response()->json([
            'message'    => 'Submission graded successfully',
            'submission' => $submission->fresh(['student:id,username,email', 'responses.question']),
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

        // responses can be sent as a JSON string (multipart) or as an array (JSON body)
        $responsesInput = $request->input('responses');
        if (is_string($responsesInput)) {
            $responsesInput = json_decode($responsesInput, true);
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

        return response()->json([
            'message'    => 'Responses graded successfully',
            'submission' => $submission->fresh(['student:id,username,email', 'responses.question']),
        ]);
    }
}
