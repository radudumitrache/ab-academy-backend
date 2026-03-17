<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DatabaseLog;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of all groups.
     */
    public function index()
    {
        $groups = Group::with(['teacher', 'students', 'assistantTeachers'])->get();

        $groups->each(fn($g) => $g->append('formatted_schedule'));

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups'  => $groups,
        ], 200);
    }

    /**
     * Get available schedule options.
     */
    public function getScheduleOptions()
    {
        return response()->json([
            'message' => 'Schedule options retrieved successfully',
            'days'    => Group::getAvailableDays(),
            'times'   => Group::getAvailableTimes(),
        ], 200);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name'              => 'required|string|max:255',
            'group_teacher'           => 'required|exists:users,id',
            'description'             => 'nullable|string',
            'schedule_days'              => 'required|array|min:1',
            'schedule_days.*.day'        => 'required|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_days.*.time'       => 'required|date_format:H:i',
            'schedule_days.*.duration'   => 'required|integer|min:1',
            'group_members'              => 'nullable|array',
            'group_members.*'            => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payload = $request->only(['group_name', 'group_teacher', 'description', 'schedule_days']);
        $group   = Group::create($payload);

        if ($request->filled('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members));
            $studentIds       = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return response()->json([
                    'message' => 'All group_members must be valid students',
                ], 422);
            }

            $group->students()->sync($studentIds);
        }

        DatabaseLog::logAction(
            'created', 'Group', $group->group_id,
            'Admin created a new group', $group->toArray()
        );

        return response()->json([
            'message' => 'Group created successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 201);
    }

    /**
     * Display the specified group.
     */
    public function show($id)
    {
        $group = Group::with(['teacher', 'students', 'assistantTeachers'])->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $group->append('formatted_schedule');

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group'   => $group,
        ], 200);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_name'              => 'sometimes|string|max:255',
            'group_teacher'           => 'sometimes|exists:users,id',
            'description'             => 'nullable|string',
            'schedule_days'              => 'sometimes|array|min:1',
            'schedule_days.*.day'        => 'required_with:schedule_days|string|in:' . implode(',', Group::getAvailableDays()),
            'schedule_days.*.time'       => 'required_with:schedule_days|date_format:H:i',
            'schedule_days.*.duration'   => 'required_with:schedule_days|integer|min:1',
            'group_members'              => 'sometimes|array',
            'group_members.*'            => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $oldData = $group->toArray();
        $payload = $request->only(['group_name', 'group_teacher', 'description', 'schedule_days']);
        $group->update($payload);

        if ($request->has('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members ?? []));
            $studentIds       = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return response()->json([
                    'message' => 'All group_members must be valid students',
                ], 422);
            }

            $group->students()->sync($studentIds);
        }

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin updated group', ['old' => $oldData, 'new' => $group->toArray()]
        );

        return response()->json([
            'message' => 'Group updated successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }

    /**
     * Remove the specified group.
     */
    public function destroy($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $groupData = $group->toArray();
        $group->delete();

        DatabaseLog::logAction(
            'deleted', 'Group', $id,
            'Admin deleted group', $groupData
        );

        return response()->json(['message' => 'Group deleted successfully'], 200);
    }

    /**
     * Add a student to a group.
     */
    public function addStudent(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
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

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin added student to group', ['student_id' => $request->student_id]
        );

        return response()->json([
            'message' => 'Student added to group successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }

    /**
     * Remove a student from a group.
     */
    public function removeStudent($groupId, $studentId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (!$group->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not in this group'], 404);
        }

        $group->students()->detach($studentId);

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin removed student from group',
            ['group_id' => $group->group_id, 'student_id' => $studentId]
        );

        return response()->json([
            'message' => 'Student removed from group successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
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

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin added student to group by username', ['username' => $request->username, 'student_id' => $student->id]
        );

        return response()->json([
            'message' => 'Student added to group successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }

    /**
     * Generate (or regenerate) a unique 8-character class code for the group.
     */
    public function generateCode($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $group->update(['class_code' => Group::generateClassCode()]);

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin generated class code', ['class_code' => $group->class_code]
        );

        return response()->json([
            'message'    => 'Class code generated successfully',
            'class_code' => $group->class_code,
        ], 200);
    }

    /**
     * Update group members for a group.
     */
    public function updateGroupMembers(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_members'   => 'required|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $requestedMembers = array_values(array_unique($request->group_members));
        $studentIds       = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

        if (count($studentIds) !== count($requestedMembers)) {
            return response()->json([
                'message' => 'All group_members must be valid students',
            ], 422);
        }

        $oldMembers = $group->students()->pluck('users.id')->all();
        $group->students()->sync($studentIds);
        $newMembers = $group->students()->pluck('users.id')->all();

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin updated group members', ['old' => $oldMembers, 'new' => $newMembers]
        );

        return response()->json([
            'message' => 'Group members updated successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }

    /**
     * Return all attendance records for a group, optionally filtered by session_date.
     * Lists every student in the group and their status per session.
     */
    public function getAttendance(Request $request, $id)
    {
        $group = Group::with('students:id,username,email')->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $query = Attendance::where('group_id', $group->group_id);

        if ($request->filled('session_date')) {
            $query->where('session_date', $request->input('session_date'));
        }

        $records = $query->with('student:id,username,email,role')
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get()
            ->map(fn($a) => [
                'student_id'   => $a->student_id,
                'username'     => $a->student?->username,
                'email'        => $a->student?->email,
                'session_date' => $a->session_date,
                'session_time' => $a->session_time,
                'status'       => $a->status,
            ]);

        return response()->json([
            'message'    => 'Attendance retrieved successfully',
            'group_id'   => $group->group_id,
            'group_name' => $group->group_name,
            'attendance' => $records,
        ]);
    }

    /**
     * Add an assistant teacher to a group (admin can assign any teacher).
     */
    public function addAssistantTeacher(Request $request, $id)
    {
        $group = Group::with('assistantTeachers')->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $teacher = Teacher::find($request->teacher_id);

        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found or user is not a teacher'], 404);
        }

        if ($teacher->id === $group->group_teacher) {
            return response()->json(['message' => 'This teacher is already the group owner'], 422);
        }

        if ($group->assistantTeachers()->where('teacher_id', $teacher->id)->exists()) {
            return response()->json(['message' => 'Teacher is already an assistant in this group'], 409);
        }

        $group->assistantTeachers()->attach($teacher->id);

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin added assistant teacher to group', ['teacher_id' => $teacher->id]
        );

        return response()->json([
            'message' => 'Assistant teacher added successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }

    /**
     * Remove an assistant teacher from a group.
     */
    public function removeAssistantTeacher($groupId, $teacherId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (!$group->assistantTeachers()->where('teacher_id', $teacherId)->exists()) {
            return response()->json(['message' => 'Teacher is not an assistant in this group'], 404);
        }

        $group->assistantTeachers()->detach($teacherId);

        DatabaseLog::logAction(
            'updated', 'Group', $group->group_id,
            'Admin removed assistant teacher from group', ['teacher_id' => $teacherId]
        );

        return response()->json([
            'message' => 'Assistant teacher removed successfully',
            'group'   => $group->load(['teacher', 'students', 'assistantTeachers']),
        ], 200);
    }
}
