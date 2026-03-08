<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * List all groups the student belongs to.
     */
    public function index()
    {
        $studentId = Auth::id();

        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id,username')
            ->get()
            ->map(fn($g) => [
                'group_id'         => $g->group_id,
                'group_name'       => $g->group_name,
                'description'      => $g->description,
                'schedule_days'    => $g->schedule_days,
                'formatted_schedule' => $g->formatted_schedule,
                'teacher'          => $g->teacher,
            ]);

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'count'   => $groups->count(),
            'groups'  => $groups,
        ]);
    }

    /**
     * Get details for a single group the student belongs to, including assigned homework.
     */
    public function show($id)
    {
        $studentId = Auth::id();

        $group = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id,username')
            ->where('group_id', $id)
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Homework assigned to this group
        $homework = Homework::where(function ($q) use ($group) {
            $q->whereJsonContains('groups_assigned', (int) $group->group_id);
        })
        ->orderByDesc('due_date')
        ->get();

        // Attach submission status for this student
        $submissionMap = HomeworkSubmission::where('student_id', $studentId)
            ->whereIn('homework_id', $homework->pluck('id'))
            ->get()
            ->keyBy('homework_id');

        $homeworkData = $homework->map(function ($hw) use ($submissionMap) {
            $sub = $submissionMap->get($hw->id);
            return [
                'id'               => $hw->id,
                'homework_title'   => $hw->homework_title,
                'homework_description' => $hw->homework_description,
                'due_date'         => $hw->due_date,
                'submission_status' => $sub ? $sub->status : 'not_started',
                'submitted_at'     => $sub ? $sub->submitted_at : null,
            ];
        });

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group'   => [
                'group_id'           => $group->group_id,
                'group_name'         => $group->group_name,
                'description'        => $group->description,
                'schedule_days'      => $group->schedule_days,
                'formatted_schedule' => $group->formatted_schedule,
                'teacher'            => $group->teacher,
                'homework'           => $homeworkData,
            ],
        ]);
    }

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
                ->keyBy(fn($s) => $s['time'])
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
