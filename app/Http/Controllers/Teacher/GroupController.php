<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Student;
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
            'group_name'              => 'required|string|max:255',
            'description'             => 'nullable|string',
            'schedule_days'           => 'required|array|min:1',
            'schedule_days.*.day'      => 'required|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_days.*.time'     => 'required|date_format:H:i',
            'schedule_days.*.duration' => 'required|integer|min:1',
            'group_members'            => 'nullable|array',
            'group_members.*'          => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $request->only(['group_name', 'description', 'schedule_days']);
        $payload['group_teacher'] = Auth::id();

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
            'group_name'              => 'sometimes|string|max:255',
            'description'             => 'nullable|string',
            'schedule_days'           => 'sometimes|array|min:1',
            'schedule_days.*.day'      => 'required_with:schedule_days|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_days.*.time'     => 'required_with:schedule_days|date_format:H:i',
            'schedule_days.*.duration' => 'required_with:schedule_days|integer|min:1',
            'group_members'            => 'sometimes|array',
            'group_members.*'         => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $request->only(['group_name', 'description', 'schedule_days']);
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

    /**
     * Generate (or regenerate) a class code for a group owned by the authenticated teacher.
     */
    public function generateCode($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->update(['class_code' => Group::generateClassCode()]);

        return response()->json([
            'message'    => 'Class code generated successfully',
            'class_code' => $group->class_code,
        ]);
    }

    /**
     * Take attendance for a group session.
     * Accepts a date, time, and a list of { student_id, status } entries.
     * Statuses: present | absent | motivated_absent
     * Upserts — calling again for the same session overwrites previous records.
     */
    public function takeAttendance(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'session_date'             => 'required|date_format:Y-m-d',
            'session_time'             => 'required|date_format:H:i',
            'attendance'               => 'required|array|min:1',
            'attendance.*.student_id'  => 'required|integer|exists:users,id',
            'attendance.*.status'      => 'required|string|in:present,absent,motivated_absent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $memberIds = $group->students()->pluck('users.id')->toArray();
        $records   = [];

        foreach ($request->attendance as $entry) {
            $studentId = $entry['student_id'];

            if (!in_array($studentId, $memberIds)) {
                return response()->json([
                    'message'    => "Student {$studentId} is not a member of this group",
                ], 422);
            }

            Attendance::updateOrCreate(
                [
                    'group_id'     => $group->group_id,
                    'student_id'   => $studentId,
                    'session_date' => $request->session_date,
                    'session_time' => $request->session_time,
                ],
                ['status' => $entry['status']]
            );

            $records[] = ['student_id' => $studentId, 'status' => $entry['status']];
        }

        return response()->json([
            'message'      => 'Attendance recorded successfully',
            'session_date' => $request->session_date,
            'session_time' => $request->session_time,
            'attendance'   => $records,
        ]);
    }

    /**
     * Join a group using a class code (student-facing; also available here for flexibility).
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

        $student = \App\Models\Student::find(Auth::id());

        if (!$student) {
            return response()->json(['message' => 'Only students can join a group via class code'], 403);
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
}
