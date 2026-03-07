<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * List all exams the authenticated student is enrolled in.
     */
    public function index()
    {
        $student = Auth::user();

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
        $student = Auth::user();

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
}
