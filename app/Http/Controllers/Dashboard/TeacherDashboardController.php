<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Homework;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = Auth::id();

        // All group IDs belonging to this teacher (respects soft deletes automatically)
        $groupIds = Group::where('group_teacher', $teacherId)->pluck('group_id')->toArray();

        // 1. Total unique students across all teacher's groups
        $totalStudents = empty($groupIds) ? 0 : DB::table('group_student')
            ->whereIn('group_id', $groupIds)
            ->distinct('student_id')
            ->count('student_id');

        // 2. Number of active groups assigned to the teacher
        $activeGroups = count($groupIds);

        // 3. Homeworks assigned to teacher's groups with a future due date
        $upcomingHomeworks = empty($groupIds) ? 0 : Homework::where('due_date', '>', today())
            ->get()
            ->filter(function (Homework $hw) use ($groupIds) {
                return !empty(array_intersect($hw->groups_assigned ?? [], $groupIds));
            })
            ->count();

        // 4. Distinct exams that students from teacher's groups are enrolled in
        $enrolledExams = empty($groupIds) ? 0 : DB::table('student_exam')
            ->whereIn(
                'student_id',
                DB::table('group_student')->whereIn('group_id', $groupIds)->pluck('student_id')
            )
            ->distinct('exam_id')
            ->count('exam_id');

        return response()->json([
            'message' => 'Teacher dashboard retrieved successfully',
            'stats'   => [
                'total_students'     => $totalStudents,
                'active_groups'      => $activeGroups,
                'upcoming_homeworks' => $upcomingHomeworks,
                'enrolled_exams'     => $enrolledExams,
            ],
        ]);
    }
}
