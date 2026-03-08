<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * List all exams the student is enrolled in.
     */
    public function index()
    {
        $student = Student::find(Auth::id());

        $exams = $student->enrolledExams()
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
     * Show a single exam the student is enrolled in.
     */
    public function show($id)
    {
        $student = Student::find(Auth::id());
        $exam    = $student->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        return response()->json([
            'message' => 'Exam retrieved successfully',
            'exam'    => $this->format($exam),
        ]);
    }

    /**
     * Create a new exam and self-enroll the student in it.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'exam_type' => 'nullable|string|max:100',
            'date'      => 'nullable|date',
        ]);

        $exam = Exam::create([
            'name'      => $request->name,
            'exam_type' => $request->exam_type,
            'date'      => $request->date,
            'status'    => Exam::STATUS_UPCOMING,
        ]);

        $exam->students()->attach(Auth::id());

        $student = Student::find(Auth::id());
        $created = $student->enrolledExams()->find($exam->id);

        return response()->json([
            'message' => 'Exam created successfully',
            'exam'    => $this->format($created),
        ], 201);
    }

    /**
     * Update the student's own score and notes on an exam.
     */
    public function updateScore(Request $request, $id)
    {
        $student = Student::find(Auth::id());
        $exam    = $student->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $request->validate([
            'student_score' => 'nullable|string|max:50',
            'notes'         => 'nullable|string',
        ]);

        $exam->pivot->update([
            'student_score' => $request->student_score,
            'notes'         => $request->notes,
        ]);

        $fresh = $student->enrolledExams()->find($id);

        return response()->json([
            'message' => 'Score updated successfully',
            'exam'    => $this->format($fresh),
        ]);
    }

    /**
     * Delete an exam the student created themselves.
     * Blocked if the admin has already set a score or feedback.
     */
    public function destroy($id)
    {
        $student = Student::find(Auth::id());
        $exam    = $student->enrolledExams()->find($id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        if ($exam->pivot->score !== null || $exam->pivot->feedback !== null) {
            return response()->json([
                'message' => 'Cannot delete an exam that has been graded by an admin.',
            ], 403);
        }

        $exam->students()->detach(Auth::id());
        $exam->delete();

        return response()->json(['message' => 'Exam deleted successfully']);
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
