<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Return the schedule overview for all groups the student belongs to.
     *
     * Each group's schedule_days array contains entries like:
     *   { "day": "Monday", "time": "09:00", "duration": 90 }
     */
    public function index()
    {
        $studentId = Auth::id();

        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id,username')
            ->get();

        $schedule = $groups->map(function ($group) {
            return [
                'group_id'          => $group->group_id,
                'group_name'        => $group->group_name,
                'description'       => $group->description,
                'teacher'           => $group->teacher ? [
                    'id'       => $group->teacher->id,
                    'username' => $group->teacher->username,
                ] : null,
                'schedule_days'     => $group->schedule_days ?? [],
                'formatted_schedule' => $group->formatted_schedule,
                'total_weekly_minutes' => $group->total_weekly_minutes,
            ];
        });

        return response()->json([
            'message'  => 'Schedule retrieved successfully',
            'schedule' => $schedule,
        ]);
    }
}
