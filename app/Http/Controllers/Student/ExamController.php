<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentPersonalExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    // ── Admin-enrolled exams (read-only) ──────────────────────────────────────

    /**
     * List all exams the authenticated student is enrolled in by the admin.
     */
    public function index()
    {
        $student = Student::find(Auth::id());

        $exams = $student->enrolledExams()
            ->orderBy('date')
            ->get()
            ->map(function ($exam) {
                return [
                    'id'         => $exam->id,
                    'name'       => $exam->name,
                    'date'       => $exam->date,
                    'status'     => $exam->status,
                    'score'      => $exam->pivot->score ?? null,
                    'feedback'   => $exam->pivot->feedback ?? null,
                ];
            });

        return response()->json([
            'message' => 'Exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ]);
    }

    /**
     * Show a single exam the student is enrolled in.
     */
    public function show($id)
    {
        $student = Student::find(Auth::id());

        $exam = $student->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam'    => [
                'id'       => $exam->id,
                'name'     => $exam->name,
                'date'     => $exam->date,
                'status'   => $exam->status,
                'score'    => $exam->pivot->score ?? null,
                'feedback' => $exam->pivot->feedback ?? null,
            ],
        ]);
    }

    // ── Personal exams (student-managed) ─────────────────────────────────────

    /**
     * List all personal exams created by the student.
     */
    public function personalIndex()
    {
        $exams = StudentPersonalExam::where('student_id', Auth::id())
            ->orderBy('date')
            ->get();

        return response()->json([
            'message' => 'Personal exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ]);
    }

    /**
     * Create a new personal exam record.
     */
    public function personalStore(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'exam_type' => 'nullable|string|max:100',
            'date'      => 'nullable|date',
            'score'     => 'nullable|string|max:50',
            'notes'     => 'nullable|string',
        ]);

        $exam = StudentPersonalExam::create([
            'student_id' => Auth::id(),
            'name'       => $request->name,
            'exam_type'  => $request->exam_type,
            'date'       => $request->date,
            'score'      => $request->score,
            'notes'      => $request->notes,
        ]);

        return response()->json([
            'message' => 'Personal exam created successfully',
            'exam'    => $exam,
        ], 201);
    }

    /**
     * Show a single personal exam.
     */
    public function personalShow($id)
    {
        $exam = StudentPersonalExam::where('student_id', Auth::id())->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        return response()->json([
            'message' => 'Personal exam retrieved successfully',
            'exam'    => $exam,
        ]);
    }

    /**
     * Update a personal exam record (e.g. add score after taking the exam).
     */
    public function personalUpdate(Request $request, $id)
    {
        $exam = StudentPersonalExam::where('student_id', Auth::id())->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'exam_type' => 'nullable|string|max:100',
            'date'      => 'nullable|date',
            'score'     => 'nullable|string|max:50',
            'notes'     => 'nullable|string',
        ]);

        $exam->update($request->only(['name', 'exam_type', 'date', 'score', 'notes']));

        return response()->json([
            'message' => 'Personal exam updated successfully',
            'exam'    => $exam,
        ]);
    }

    /**
     * Delete a personal exam record.
     */
    public function personalDestroy($id)
    {
        $exam = StudentPersonalExam::where('student_id', Auth::id())->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $exam->delete();

        return response()->json(['message' => 'Personal exam deleted successfully']);
    }
}
