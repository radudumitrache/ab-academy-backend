<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\TestQuestionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TestSubmissionController extends Controller
{
    /**
     * List all submitted submissions for a test the teacher owns.
     */
    public function index($testId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submissions = TestSubmission::with(['student:id,username,email', 'responses.question'])
            ->where('test_id', $testId)
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
    public function show($testId, $submissionId)
    {
        $test = Test::where('test_teacher', Auth::id())->find($testId);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $submission = TestSubmission::with(['student:id,username,email', 'responses.question'])
            ->where('test_id', $testId)
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

        return response()->json([
            'message'    => 'Submission graded successfully',
            'submission' => $submission->fresh(['student:id,username,email', 'responses.question']),
        ]);
    }

    /**
     * Grade individual question responses within a test submission.
     *
     * Accepts an array of { response_id, grade, observation } objects.
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

        $validator = Validator::make($request->all(), [
            'responses'                  => 'required|array|min:1',
            'responses.*.response_id'    => 'required|integer',
            'responses.*.grade'          => 'nullable|string|max:50',
            'responses.*.observation'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        foreach ($validator->validated()['responses'] as $item) {
            $response = TestQuestionResponse::where('submission_id', $submissionId)
                ->where('response_id', $item['response_id'])
                ->first();

            if (!$response) {
                return response()->json([
                    'message' => "Response {$item['response_id']} not found in this submission",
                ], 404);
            }

            $response->update([
                'grade'       => $item['grade'] ?? $response->grade,
                'observation' => $item['observation'] ?? $response->observation,
            ]);
        }

        return response()->json([
            'message'    => 'Responses graded successfully',
            'submission' => $submission->fresh(['student:id,username,email', 'responses.question']),
        ]);
    }
}
