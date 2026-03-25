<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    /**
     * Display a listing of all exams with full pivot data.
     */
    public function index()
    {
        $exams = Exam::with(['students' => function ($q) {
            $q->withPivot('score', 'feedback', 'student_score', 'notes');
        }])->get();

        return response()->json([
            'message' => 'Exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ], 200);
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'exam_type'    => 'nullable|string|max:100',
            'date'         => 'required|date_format:Y-m-d',
            'status'       => 'nullable|in:upcoming,to_be_corrected,passed,failed',
            'student_ids'  => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exam = Exam::create([
            'name'      => $request->name,
            'exam_type' => $request->exam_type,
            'date'      => $request->date,
            'status'    => $request->status ?? Exam::STATUS_UPCOMING,
        ]);

        $exam->statusHistory()->create([
            'old_status'          => null,
            'new_status'          => $exam->status,
            'changed_by_user_id'  => Auth::id(),
        ]);

        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $studentIds = Student::whereIn('id', $request->student_ids)->pluck('id')->all();
            $exam->students()->attach($studentIds);
        }

        DatabaseLog::logAction('create', Exam::class, $exam->id, "Exam '{$exam->name}' created");

        return response()->json([
            'message' => 'Exam created successfully',
            'exam'    => $exam->load(['students', 'statusHistory']),
        ], 201);
    }

    /**
     * Display the specified exam with full pivot data per student.
     */
    public function show($id)
    {
        $exam = Exam::with([
            'students' => fn($q) => $q->withPivot('score', 'feedback', 'student_score', 'notes'),
            'statusHistory',
        ])->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam'    => $exam,
        ], 200);
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'exam_type' => 'nullable|string|max:100',
            'date'      => 'sometimes|required|date_format:Y-m-d',
            'status'    => 'sometimes|required|in:upcoming,to_be_corrected,passed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->filled('status') && $exam->status !== $request->status) {
            $exam->updateStatus($request->status, Auth::id());
        }

        if ($request->has('name')) {
            $exam->name = $request->input('name');
        }
        if ($request->has('exam_type')) {
            $exam->exam_type = $request->input('exam_type');
        }
        if ($request->has('date')) {
            $exam->date = $request->input('date');
        }
        $exam->save();

        DatabaseLog::logAction('update', Exam::class, $exam->id, "Exam '{$exam->name}' updated");

        return response()->json([
            'message' => 'Exam updated successfully',
            'exam'    => $exam->fresh(['students', 'statusHistory']),
        ], 200);
    }

    /**
     * Remove the specified exam.
     */
    public function destroy($id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $examName = $exam->name;
        $exam->delete();

        DatabaseLog::logAction('delete', Exam::class, $id, "Exam '{$examName}' deleted");

        return response()->json(['message' => 'Exam deleted successfully'], 200);
    }

    /**
     * Enroll students in an exam.
     */
    public function enrollStudents(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $studentIds = Student::whereIn('id', $request->student_ids)->pluck('id')->all();

        if (count($studentIds) !== count($request->student_ids)) {
            return response()->json(['message' => 'All student_ids must belong to students'], 422);
        }

        $exam->students()->syncWithoutDetaching($studentIds);

        DatabaseLog::logAction('update', Exam::class, $exam->id, count($studentIds) . " student(s) enrolled in exam '{$exam->name}'");

        return response()->json([
            'message' => 'Students enrolled in exam successfully',
            'exam'    => $exam->fresh(['students']),
        ], 200);
    }

    /**
     * Set the admin score and feedback for a specific student on an exam.
     */
    public function gradeStudent(Request $request, $examId, $studentId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        if (!$exam->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this exam'], 404);
        }

        $request->validate([
            'score'    => 'nullable|numeric',
            'feedback' => 'nullable|string',
        ]);

        $exam->students()->updateExistingPivot($studentId, [
            'score'    => $request->score,
            'feedback' => $request->feedback,
        ]);

        DatabaseLog::logAction('update', Exam::class, $examId, "Student #{$studentId} graded in exam '{$exam->name}'");

        return response()->json([
            'message' => 'Student graded successfully',
            'exam'    => $exam->load(['students' => fn($q) => $q->withPivot('score', 'feedback', 'student_score', 'notes')]),
        ], 200);
    }

    /**
     * Remove a student from an exam.
     */
    public function removeStudent($examId, $studentId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        if (!$exam->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this exam'], 404);
        }

        $exam->students()->detach($studentId);

        DatabaseLog::logAction('update', Exam::class, $examId, "Student '{$student->username}' removed from exam '{$exam->name}'");

        return response()->json([
            'message' => 'Student removed from exam successfully',
            'exam'    => $exam->fresh(['students']),
        ], 200);
    }
}
