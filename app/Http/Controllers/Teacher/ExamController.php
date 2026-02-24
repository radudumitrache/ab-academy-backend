<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    /**
     * Collect all unique student IDs across the authenticated teacher's groups.
     */
    private function getTeacherStudentIds(): array
    {
        $groupIds = Group::where('group_teacher', Auth::id())->pluck('group_id')->toArray();

        if (empty($groupIds)) {
            return [];
        }

        return DB::table('group_student')
            ->whereIn('group_id', $groupIds)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * List all exams that at least one student from the teacher's groups is enrolled in.
     */
    public function index()
    {
        $studentIds = $this->getTeacherStudentIds();

        if (empty($studentIds)) {
            return response()->json([
                'message' => 'Exams retrieved successfully',
                'count'   => 0,
                'exams'   => [],
            ]);
        }

        $examIds = DB::table('student_exam')
            ->whereIn('student_id', $studentIds)
            ->pluck('exam_id')
            ->unique()
            ->toArray();

        $exams = Exam::with(['students'])->whereIn('id', $examIds)->get();

        return response()->json([
            'message' => 'Exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ]);
    }

    /**
     * Show a single exam — accessible only if at least one of the teacher's students is enrolled.
     */
    public function show($id)
    {
        $exam = Exam::with(['students', 'statusHistory'])->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $studentIds     = $this->getTeacherStudentIds();
        $enrolledIds    = $exam->students->pluck('id')->toArray();

        if (empty(array_intersect($enrolledIds, $studentIds))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam'    => $exam,
        ]);
    }

    /**
     * Create a new exam, optionally enrolling students from the teacher's groups.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'date'          => 'required|date_format:Y-m-d',
            'status'        => 'nullable|in:upcoming,to_be_corrected,passed,failed',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exam = Exam::create([
            'name'   => $request->name,
            'date'   => $request->date,
            'status' => $request->status ?? Exam::STATUS_UPCOMING,
        ]);

        $exam->statusHistory()->create([
            'old_status'         => null,
            'new_status'         => $exam->status,
            'changed_by_user_id' => Auth::id(),
        ]);

        if ($request->filled('student_ids')) {
            $teacherStudentIds = $this->getTeacherStudentIds();
            $requestedIds      = array_values(array_unique($request->student_ids));
            $validStudentIds   = Student::whereIn('id', $requestedIds)->pluck('id')->toArray();
            $authorizedIds     = array_values(array_intersect($validStudentIds, $teacherStudentIds));

            if (count($authorizedIds) !== count($requestedIds)) {
                $exam->delete();
                return response()->json([
                    'message' => 'All student_ids must be valid students belonging to your groups',
                ], 422);
            }

            $exam->students()->attach($authorizedIds);
        }

        return response()->json([
            'message' => 'Exam created successfully',
            'exam'    => $exam->load(['students', 'statusHistory']),
        ], 201);
    }

    /**
     * Enroll one or more students (from the teacher's groups) in an exam.
     */
    public function enrollStudents(Request $request, $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $teacherStudentIds = $this->getTeacherStudentIds();
        $requestedIds      = array_values(array_unique($request->student_ids));
        $validStudentIds   = Student::whereIn('id', $requestedIds)->pluck('id')->toArray();
        $authorizedIds     = array_values(array_intersect($validStudentIds, $teacherStudentIds));

        if (count($authorizedIds) !== count($requestedIds)) {
            return response()->json([
                'message' => 'All student_ids must be valid students belonging to your groups',
            ], 422);
        }

        $exam->students()->syncWithoutDetaching($authorizedIds);

        return response()->json([
            'message' => 'Students enrolled in exam successfully',
            'exam'    => $exam->fresh(['students']),
        ]);
    }

    /**
     * Remove a student from an exam — student must belong to one of the teacher's groups.
     */
    public function removeStudent($examId, $studentId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $teacherStudentIds = $this->getTeacherStudentIds();

        if (!in_array((int) $studentId, $teacherStudentIds)) {
            return response()->json(['message' => 'Student does not belong to your groups'], 403);
        }

        if (!$exam->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this exam'], 404);
        }

        $exam->students()->detach($studentId);

        return response()->json([
            'message' => 'Student removed from exam successfully',
            'exam'    => $exam->fresh(['students']),
        ]);
    }
}
