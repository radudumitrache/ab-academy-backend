<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\HomeworkSubmission;
use App\Models\TestSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPerformanceController extends Controller
{
    /**
     * Get performance data for all students in the teacher's groups
     * (main teacher or assistant teacher).
     */
    public function index()
    {
        $teacherId = Auth::id();
        $studentIds = $this->getStudentIds($teacherId);

        if ($studentIds->isEmpty()) {
            return response()->json([
                'message' => 'Student performance retrieved successfully',
                'count'   => 0,
                'data'    => [],
            ]);
        }

        $students = \App\Models\Student::whereIn('id', $studentIds)
            ->get(['id', 'username', 'email', 'admin_notes']);

        $data = $students->map(fn($student) => [
            'student'     => [
                'id'          => $student->id,
                'username'    => $student->username,
                'email'       => $student->email,
                'admin_notes' => $student->admin_notes,
            ],
            'performance' => $this->buildPerformance($student->id),
        ]);

        return response()->json([
            'message' => 'Student performance retrieved successfully',
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    /**
     * Get performance data for a single student, only if they belong
     * to one of the teacher's groups.
     */
    public function show(int $id)
    {
        $teacherId  = Auth::id();
        $studentIds = $this->getStudentIds($teacherId);

        if (!$studentIds->contains($id)) {
            return response()->json(['message' => 'Student not found in your groups'], 403);
        }

        $student = \App\Models\Student::findOrFail($id);

        return response()->json([
            'message'     => 'Student performance retrieved successfully',
            'student'     => [
                'id'          => $student->id,
                'username'    => $student->username,
                'email'       => $student->email,
                'admin_notes' => $student->admin_notes,
            ],
            'performance' => $this->buildPerformance($student->id),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Collect unique student IDs across all groups the teacher owns or assists.
     */
    private function getStudentIds(int $teacherId): \Illuminate\Support\Collection
    {
        $ownedGroups = Group::where('group_teacher', $teacherId)
            ->with('students:id')
            ->get();

        $assistedGroups = Group::whereHas('assistantTeachers', fn($q) => $q->where('teacher_id', $teacherId))
            ->with('students:id')
            ->get();

        return $ownedGroups->merge($assistedGroups)
            ->flatMap(fn($g) => $g->students->pluck('id'))
            ->unique()
            ->values();
    }

    private function buildPerformance(int $studentId): array
    {
        $homeworkSubmissions = HomeworkSubmission::where('student_id', $studentId)
            ->with('homework:id,homework_title')
            ->get(['id', 'homework_id', 'status', 'grade', 'observation', 'submitted_at']);

        $testSubmissions = TestSubmission::where('student_id', $studentId)
            ->with('test:id,title')
            ->get(['id', 'test_id', 'status', 'grade', 'observation', 'submitted_at']);

        $homeworkGrades = $homeworkSubmissions->whereNotNull('grade')->map(fn($s) => (float) $s->grade);
        $testGrades     = $testSubmissions->whereNotNull('grade')->map(fn($s) => (float) $s->grade);
        $allGrades      = $homeworkGrades->merge($testGrades);

        return [
            'summary' => [
                'homework_submitted' => $homeworkSubmissions->count(),
                'homework_graded'    => $homeworkGrades->count(),
                'test_submitted'     => $testSubmissions->count(),
                'test_graded'        => $testGrades->count(),
                'average_grade'      => $allGrades->count()
                    ? round($allGrades->avg(), 2)
                    : null,
            ],
            'homework_submissions' => $homeworkSubmissions->map(fn($s) => [
                'id'           => $s->id,
                'homework_id'  => $s->homework_id,
                'title'        => $s->homework?->homework_title,
                'status'       => $s->status,
                'grade'        => $s->grade,
                'observation'  => $s->observation,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
            ]),
            'test_submissions' => $testSubmissions->map(fn($s) => [
                'id'           => $s->id,
                'test_id'      => $s->test_id,
                'title'        => $s->test?->title,
                'status'       => $s->status,
                'grade'        => $s->grade,
                'observation'  => $s->observation,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
            ]),
        ];
    }
}
