<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\HomeworkSubmission;
use App\Models\TestSubmission;
use Illuminate\Http\Request;

class StudentPerformanceController extends Controller
{
    /**
     * Get performance data for a single student (admin scope — no group restrictions).
     */
    public function show(int $id)
    {
        $student = Student::findOrFail($id);

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

    /**
     * Get performance data for all students (admin scope).
     */
    public function index()
    {
        $students = Student::all(['id', 'username', 'email', 'admin_notes']);

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
            'message' => 'All student performance retrieved successfully',
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    // ── Shared builder ────────────────────────────────────────────────────────

    protected function buildPerformance(int $studentId): array
    {
        $homeworkSubmissions = HomeworkSubmission::where('student_id', $studentId)
            ->with('homework:id,homework_title')
            ->get(['id', 'homework_id', 'status', 'grade', 'observation', 'submitted_at']);

        $testSubmissions = TestSubmission::where('student_id', $studentId)
            ->with('test:id,title')
            ->get(['id', 'test_id', 'status', 'grade', 'observation', 'submitted_at']);

        $homeworkGrades  = $homeworkSubmissions->whereNotNull('grade')->pluck('grade')->map(fn($g) => (float) $g);
        $testGrades      = $testSubmissions->whereNotNull('grade')->pluck('grade')->map(fn($g) => (float) $g);
        $allGrades       = $homeworkGrades->merge($testGrades);

        return [
            'summary' => [
                'homework_submitted'  => $homeworkSubmissions->count(),
                'homework_graded'     => $homeworkGrades->count(),
                'test_submitted'      => $testSubmissions->count(),
                'test_graded'         => $testGrades->count(),
                'average_grade'       => $allGrades->count()
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
