<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Group;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentDetailController extends Controller
{
    /**
     * Get groups a specific student belongs to
     */
    public function getStudentGroups($id)
    {
        $student = Student::findOrFail($id);
        
        $groups = $student->groups()->with('teacher')->get();
        
        return response()->json([
            'message' => 'Student groups retrieved successfully',
            'student_id' => $id,
            'groups' => $groups
        ]);
    }
    
    /**
     * Get exam data for a specific student
     */
    public function getStudentExams($id)
    {
        $student = Student::findOrFail($id);
        
        // Get all exams the student is enrolled in
        $exams = $student->enrolledExams()
            ->with('teacher')
            ->get()
            ->map(function ($exam) {
                // Get the pivot data (score, feedback)
                $pivotData = $exam->pivot;
                
                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'date' => $exam->date,
                    'status' => $exam->status,
                    'teacher' => [
                        'id' => $exam->teacher->id,
                        'username' => $exam->teacher->username
                    ],
                    'score' => $pivotData->score,
                    'feedback' => $pivotData->feedback,
                ];
            });
        
        // Categorize exams
        $upcomingExams = $exams->where('status', 'upcoming')->values();
        $completedExams = $exams->whereIn('status', ['passed', 'failed'])->values();
        $toBeGradedExams = $exams->where('status', 'to_be_corrected')->values();
        
        // Get next exam
        $nextExam = $upcomingExams->sortBy('date')->first();
        
        return response()->json([
            'message' => 'Student exams retrieved successfully',
            'student_id' => $id,
            'exams_summary' => [
                'upcoming_count' => $upcomingExams->count(),
                'completed_count' => $completedExams->count(),
                'to_be_graded_count' => $toBeGradedExams->count(),
                'next_exam' => $nextExam
            ],
            'exams' => [
                'upcoming' => $upcomingExams,
                'completed' => $completedExams,
                'to_be_graded' => $toBeGradedExams
            ]
        ]);
    }
    
    /**
     * Get payment information for a specific student
     */
    public function getStudentPayments($id)
    {
        $student = Student::findOrFail($id);
        
        // Since we don't have a payments table yet, we'll return a placeholder
        return response()->json([
            'message' => 'Student payments retrieved successfully',
            'student_id' => $id,
            'payments' => [
                'status' => 'No payment records found',
                'data' => []
            ],
            'note' => 'Payment system not yet implemented'
        ]);
    }
}
