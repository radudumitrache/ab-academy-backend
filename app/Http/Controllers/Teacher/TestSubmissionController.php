<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSubmission;
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
}
