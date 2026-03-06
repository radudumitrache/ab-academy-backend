<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Join a group using a class code.
     */
    public function joinByCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $group = Group::where('class_code', strtoupper($request->class_code))->first();

        if (!$group) {
            return response()->json(['message' => 'Invalid class code'], 404);
        }

        if ($group->students()->where('student_id', Auth::id())->exists()) {
            return response()->json(['message' => 'You are already in this group'], 409);
        }

        $group->students()->attach(Auth::id());

        return response()->json([
            'message' => 'Joined group successfully',
            'group'   => $group->load('students'),
        ]);
    }

    /**
     * Return a summary of course hours the authenticated student must attend,
     * broken down per group, plus overall attendance stats.
     *
     * "Required hours" = total weekly minutes * number of weeks since the student
     * joined the group (based on the earliest attendance record or join date).
     *
     * Response includes per-group:
     *  - total_sessions_held       — sessions with at least one attendance record
     *  - sessions_present          — sessions where the student was present
     *  - sessions_absent           — sessions where the student was absent
     *  - sessions_motivated_absent — sessions where the student had a motivated absence
     *  - total_minutes_scheduled   — sum of duration across all schedule slots per session held
     *  - minutes_attended          — minutes from sessions the student was present
     */
    public function courseHours()
    {
        $studentId = Auth::id();

        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->get();

        $summary = [];
        $totalMinutesScheduled = 0;
        $totalMinutesAttended  = 0;

        foreach ($groups as $group) {
            $records = Attendance::where('group_id', $group->group_id)
                ->where('student_id', $studentId)
                ->get();

            $present          = $records->where('status', 'present')->count();
            $absent           = $records->where('status', 'absent')->count();
            $motivatedAbsent  = $records->where('status', 'motivated_absent')->count();
            $totalSessions    = $records->count();

            // Find the duration for a given session_time from the group's schedule_days
            $durationMap = collect($group->schedule_days ?? [])
                ->keyBy('time')
                ->map(fn($s) => $s['duration'] ?? 0);

            $minutesScheduled = $records->sum(fn($r) => $durationMap[$r->session_time] ?? 0);
            $minutesAttended  = $records->where('status', 'present')
                ->sum(fn($r) => $durationMap[$r->session_time] ?? 0);

            $totalMinutesScheduled += $minutesScheduled;
            $totalMinutesAttended  += $minutesAttended;

            $summary[] = [
                'group_id'                   => $group->group_id,
                'group_name'                 => $group->group_name,
                'weekly_minutes'             => $group->total_weekly_minutes,
                'total_sessions_held'        => $totalSessions,
                'sessions_present'           => $present,
                'sessions_absent'            => $absent,
                'sessions_motivated_absent'  => $motivatedAbsent,
                'total_minutes_scheduled'    => $minutesScheduled,
                'minutes_attended'           => $minutesAttended,
            ];
        }

        return response()->json([
            'message'                => 'Course hours retrieved successfully',
            'total_minutes_scheduled' => $totalMinutesScheduled,
            'total_minutes_attended'  => $totalMinutesAttended,
            'groups'                 => $summary,
        ]);
    }
}
