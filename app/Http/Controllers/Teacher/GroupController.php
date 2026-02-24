<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * List all groups that belong to the authenticated teacher.
     */
    public function index()
    {
        $groups = Group::with(['students'])
            ->where('group_teacher', Auth::id())
            ->get()
            ->each(fn($g) => $g->append('formatted_schedule'));

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups'  => $groups,
        ]);
    }

    /**
     * Return the available schedule days and time slots.
     */
    public function getScheduleOptions()
    {
        return response()->json([
            'message' => 'Schedule options retrieved successfully',
            'days'    => Group::getAvailableDays(),
            'times'   => Group::getAvailableTimes(),
        ]);
    }

    /**
     * Create a new group owned by the authenticated teacher.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name'    => 'required|string|max:255',
            'description'   => 'nullable|string',
            'schedule_day'  => 'required|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_time' => 'required|date_format:H:i',
            'group_members' => 'nullable|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $request->only(['group_name', 'description', 'schedule_day', 'schedule_time']);
        $payload['group_teacher'] = Auth::id();
        $payload['normal_schedule'] = $this->buildNormalSchedule(
            $payload['schedule_day'],
            $payload['schedule_time']
        );

        $group = Group::create($payload);

        if ($request->filled('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members));
            $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                $group->delete();
                return response()->json([
                    'message' => 'All group_members must be valid students',
                ], 422);
            }

            $group->students()->sync($studentIds);
        }

        return response()->json([
            'message' => 'Group created successfully',
            'group'   => $group->load('students'),
        ], 201);
    }

    /**
     * Show a single group owned by the authenticated teacher.
     */
    public function show($id)
    {
        $group = Group::with(['students'])->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->append('formatted_schedule');

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group'   => $group,
        ]);
    }

    /**
     * Update a group owned by the authenticated teacher.
     */
    public function update(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'group_name'    => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'schedule_day'  => 'sometimes|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_time' => 'sometimes|date_format:H:i',
            'group_members' => 'sometimes|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $request->only(['group_name', 'description', 'schedule_day', 'schedule_time']);

        $day  = $payload['schedule_day']  ?? $group->schedule_day;
        $time = $payload['schedule_time'] ?? $group->schedule_time;
        if (isset($payload['schedule_day']) || isset($payload['schedule_time'])) {
            $payload['normal_schedule'] = $this->buildNormalSchedule($day, $time);
        }

        $group->update($payload);

        if ($request->has('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members ?? []));
            $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return response()->json([
                    'message' => 'All group_members must be valid students',
                ], 422);
            }

            $group->students()->sync($studentIds);
        }

        return response()->json([
            'message' => 'Group updated successfully',
            'group'   => $group->load('students'),
        ]);
    }

    /**
     * Soft-delete a group owned by the authenticated teacher.
     */
    public function destroy($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }

    /**
     * Add a student to a group owned by the authenticated teacher.
     */
    public function addStudent(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $student = Student::find($request->student_id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found or user is not a student',
            ], 404);
        }

        if ($group->students()->where('student_id', $request->student_id)->exists()) {
            return response()->json(['message' => 'Student is already in this group'], 409);
        }

        $group->students()->attach($request->student_id);

        return response()->json([
            'message' => 'Student added to group successfully',
            'group'   => $group->load('students'),
        ]);
    }

    /**
     * Add a student to a group by their username.
     */
    public function addStudentByUsername(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $student = Student::where('username', $request->username)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Student not found or user is not a student',
            ], 404);
        }

        if ($group->students()->where('student_id', $student->id)->exists()) {
            return response()->json(['message' => 'Student is already in this group'], 409);
        }

        $group->students()->attach($student->id);

        return response()->json([
            'message' => 'Student added to group successfully',
            'group'   => $group->load('students'),
        ]);
    }

    /**
     * Remove a student from a group owned by the authenticated teacher.
     */
    public function removeStudent($groupId, $studentId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$group->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not in this group'], 404);
        }

        $group->students()->detach($studentId);

        return response()->json([
            'message' => 'Student removed from group successfully',
            'group'   => $group->load('students'),
        ]);
    }

    // -------------------------------------------------------------------------

    private function buildNormalSchedule(string $day, string $time): string
    {
        $today       = new DateTime();
        $dayMap      = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
                        'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
        $target      = $dayMap[$day] ?? (int) $today->format('N');
        $current     = (int) $today->format('N');
        $daysToAdd   = $target >= $current ? $target - $current : 7 - ($current - $target);
        $next        = clone $today;
        $next->modify("+{$daysToAdd} days");

        return $next->format('Y-m-d') . ' ' . $time . ':00';
    }
}
