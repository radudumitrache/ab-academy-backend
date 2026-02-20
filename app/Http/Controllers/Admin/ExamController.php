<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    /**
     * Display a listing of all exams.
     */
    public function index()
    {
        $exams = Exam::with(['teacher'])->get();

        return response()->json([
            'message' => 'Exams retrieved successfully',
            'count' => $exams->count(),
            'exams' => $exams
        ], 200);
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'teacher_id' => 'required|exists:users,id',
            'status' => 'nullable|in:upcoming,to_be_corrected,passed,failed',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify teacher_id belongs to a teacher
        $teacher = Teacher::find($request->teacher_id);
        if (!$teacher) {
            return response()->json([
                'message' => 'The provided teacher_id does not belong to a teacher'
            ], 422);
        }

        // Create the exam
        $exam = Exam::create([
            'name' => $request->name,
            'date' => $request->date,
            'teacher_id' => $request->teacher_id,
            'status' => $request->status ?? Exam::STATUS_UPCOMING,
        ]);

        // Record initial status in history
        $exam->statusHistory()->create([
            'old_status' => null,
            'new_status' => $exam->status,
            'changed_by_user_id' => Auth::id(),
        ]);

        // Enroll students if provided
        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $studentIds = Student::whereIn('id', $request->student_ids)->pluck('id')->all();
            $exam->students()->attach($studentIds);
        }

        return response()->json([
            'message' => 'Exam created successfully',
            'exam' => $exam->load(['teacher', 'students', 'statusHistory'])
        ], 201);
    }

    /**
     * Display the specified exam.
     */
    public function show($id)
    {
        $exam = Exam::with(['teacher', 'students', 'statusHistory'])->find($id);

        if (!$exam) {
            return response()->json([
                'message' => 'Exam not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam' => $exam
        ], 200);
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'message' => 'Exam not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'teacher_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:upcoming,to_be_corrected,passed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify teacher_id belongs to a teacher if provided
        if ($request->filled('teacher_id')) {
            $teacher = Teacher::find($request->teacher_id);
            if (!$teacher) {
                return response()->json([
                    'message' => 'The provided teacher_id does not belong to a teacher'
                ], 422);
            }
        }

        // Handle status update separately to track history
        if ($request->filled('status') && $exam->status !== $request->status) {
            $exam->updateStatus($request->status, Auth::id());
        }

        // Update other fields
        $exam->fill($request->except('status'));
        $exam->save();

        return response()->json([
            'message' => 'Exam updated successfully',
            'exam' => $exam->fresh(['teacher', 'students', 'statusHistory'])
        ], 200);
    }

    /**
     * Remove the specified exam.
     */
    public function destroy($id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'message' => 'Exam not found'
            ], 404);
        }

        $exam->delete();

        return response()->json([
            'message' => 'Exam deleted successfully'
        ], 200);
    }

    /**
     * Enroll students in an exam.
     */
    public function enrollStudents(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'message' => 'Exam not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify all IDs belong to students
        $studentIds = Student::whereIn('id', $request->student_ids)->pluck('id')->all();
        
        if (count($studentIds) !== count($request->student_ids)) {
            return response()->json([
                'message' => 'All student_ids must belong to students'
            ], 422);
        }

        // Attach students to the exam
        $exam->students()->syncWithoutDetaching($studentIds);

        return response()->json([
            'message' => 'Students enrolled in exam successfully',
            'exam' => $exam->fresh(['teacher', 'students'])
        ], 200);
    }

    /**
     * Remove a student from an exam.
     */
    public function removeStudent($examId, $studentId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json([
                'message' => 'Exam not found'
            ], 404);
        }

        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found'
            ], 404);
        }

        // Check if student is enrolled in this exam
        if (!$exam->students()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'Student is not enrolled in this exam'
            ], 404);
        }

        // Detach the student from the exam
        $exam->students()->detach($studentId);

        return response()->json([
            'message' => 'Student removed from exam successfully',
            'exam' => $exam->fresh(['teacher', 'students'])
        ], 200);
    }
}
