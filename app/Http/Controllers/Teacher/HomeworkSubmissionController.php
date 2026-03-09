<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeworkSubmissionController extends Controller
{
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
}
