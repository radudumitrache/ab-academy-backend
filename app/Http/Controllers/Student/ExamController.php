<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * List all exams the student is enrolled in.
     */
    public function index()
    {
        $exams = $this->enrolledExams()
            ->orderBy('date')
            ->get()
            ->map(fn($exam) => $this->format($exam));

        return response()->json([
            'message' => 'Exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ]);
    }

    /**
     * List all exams the student is NOT yet enrolled in (available to register).
     */
    public function available()
    {
        $enrolledIds = $this->enrolledExams()->pluck('exams.id');

        $exams = Exam::whereNotIn('id', $enrolledIds)
            ->where('status', Exam::STATUS_UPCOMING)
            ->orderBy('date')
            ->get()
            ->map(fn($exam) => [
                'id'        => $exam->id,
                'name'      => $exam->name,
                'exam_type' => $exam->exam_type,
                'date'      => $exam->date,
                'status'    => $exam->status,
            ]);

        return response()->json([
            'message' => 'Available exams retrieved successfully',
            'count'   => $exams->count(),
            'exams'   => $exams,
        ]);
    }

    /**
     * Show a single exam the student is enrolled in.
     */
    public function show($id)
    {
        $exam = $this->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found or not enrolled'], 404);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam'    => $this->format($exam),
        ]);
    }

    /**
     * Register (self-enroll) the student in an existing exam.
     * Body: { "exam_id": 5 }
     */
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
        ]);

        $exam = Exam::find($request->exam_id);

        if ($exam->status !== Exam::STATUS_UPCOMING) {
            return response()->json(['message' => 'Cannot register for an exam that is not upcoming'], 422);
        }

        // Check if already enrolled
        $alreadyEnrolled = $this->enrolledExams()->where('exams.id', $exam->id)->exists();
        if ($alreadyEnrolled) {
            return response()->json(['message' => 'Already registered for this exam'], 409);
        }

        $this->enrolledExams()->attach($exam->id);

        $enrolled = $this->enrolledExams()->find($exam->id);

        return response()->json([
            'message' => 'Successfully registered for exam',
            'exam'    => $this->format($enrolled),
        ], 201);
    }

    /**
     * Unregister (self-unenroll) from an exam.
     * Blocked if the admin has already set a score or feedback.
     */
    public function destroy($id)
    {
        $exam = $this->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found or not enrolled'], 404);
        }

        if ($exam->pivot->score !== null || $exam->pivot->feedback !== null) {
            return response()->json([
                'message' => 'Cannot unregister from an exam that has been graded.',
            ], 403);
        }

        $this->enrolledExams()->detach($id);

        return response()->json(['message' => 'Successfully unregistered from exam']);
    }

    /**
     * Update the student's own score and notes on an exam.
     */
    public function updateScore(Request $request, $id)
    {
        $exam = $this->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found or not enrolled'], 404);
        }

        $request->validate([
            'student_score' => 'nullable|string|max:50',
            'notes'         => 'nullable|string',
        ]);

        $exam->pivot->update([
            'student_score' => $request->student_score,
            'notes'         => $request->notes,
        ]);

        $fresh = $this->enrolledExams()->find($id);

        return response()->json([
            'message' => 'Score updated successfully',
            'exam'    => $this->format($fresh),
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Return the enrolledExams BelongsToMany relation for the authenticated user.
     * Works regardless of whether Auth::user() is a Student, User, or any subclass.
     */
    private function enrolledExams()
    {
        return Auth::user()
            ->belongsToMany(Exam::class, 'student_exam', 'student_id', 'exam_id')
            ->withPivot('score', 'feedback', 'student_score', 'notes')
            ->withTimestamps();
    }

    private function format(Exam $exam): array
    {
        return [
            'id'            => $exam->id,
            'name'          => $exam->name,
            'exam_type'     => $exam->exam_type,
            'date'          => $exam->date,
            'status'        => $exam->status,
            'admin_score'   => $exam->pivot->score ?? null,
            'feedback'      => $exam->pivot->feedback ?? null,
            'student_score' => $exam->pivot->student_score ?? null,
            'notes'         => $exam->pivot->notes ?? null,
        ];
    }
}
