<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Convert a group's schedule_days times from UTC to the requesting user's timezone,
     * rebuild formatted_schedule accordingly, and return the group as an array.
     */
    private function formatGroup(Group $group): array
    {
        $userTz = Auth::user()->effective_timezone;
        $rawDays = $group->schedule_days ?? [];

        $convertedDays = array_map(function ($slot) use ($userTz) {
            $slot['time'] = TimezoneHelper::scheduleTimeFromUtc($slot['time'], $userTz);
            return $slot;
        }, $rawDays);

        $formattedParts = array_map(function ($s) {
            $label = "{$s['day']} at {$s['time']}";
            if (!empty($s['duration'])) {
                $label .= " ({$s['duration']}min)";
            }
            return $label;
        }, $convertedDays);

        $data = $group->toArray();
        $data['schedule_days']      = $convertedDays;
        $data['formatted_schedule'] = implode(', ', $formattedParts) ?: null;

        return $data;
    }

    /**
     * List all groups that belong to the authenticated teacher.
     */
    public function index()
    {
        $teacherId = Auth::id();

        $groups = Group::with(['students', 'assistantTeachers'])
            ->where(function ($q) use ($teacherId) {
                $q->where('group_teacher', $teacherId)
                  ->orWhereHas('assistantTeachers', fn($q2) => $q2->where('teacher_id', $teacherId));
            })
            ->get();

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups'  => $groups->map(fn($g) => $this->formatGroup($g)),
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

        // Convert schedule_days times from actor's timezone to UTC before storing
        if (!empty($payload['schedule_days'])) {
            $actorTz = Auth::user()->effective_timezone;
            $payload['schedule_days'] = array_map(function ($slot) use ($actorTz) {
                $slot['time'] = TimezoneHelper::scheduleTimeToUtc($slot['time'], $actorTz);
                return $slot;
            }, $payload['schedule_days']);
        }

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
            'group'   => $this->formatGroup($group->load('students')),
        ], 201);
    }

    /**
     * Show a single group owned by the authenticated teacher.
     */
    public function show($id)
    {
        $group = Group::with(['students', 'assistantTeachers'])->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (!$group->canManage(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group'   => $this->formatGroup($group),
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

        if (!$group->canManage(Auth::id())) {
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

        // Convert schedule_days times from actor's timezone to UTC before storing
        if (!empty($payload['schedule_days'])) {
            $actorTz = Auth::user()->effective_timezone;
            $payload['schedule_days'] = array_map(function ($slot) use ($actorTz) {
                $slot['time'] = TimezoneHelper::scheduleTimeToUtc($slot['time'], $actorTz);
                return $slot;
            }, $payload['schedule_days']);
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
            'group'   => $this->formatGroup($group->load('students')),
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

        if (!$group->canManage(Auth::id())) {
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

        if (!$group->canManage(Auth::id())) {
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
            'group'   => $this->formatGroup($group->load('students')),
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

        if (!$group->canManage(Auth::id())) {
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
            'group'   => $this->formatGroup($group->load('students')),
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

        if (!$group->canManage(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$group->students()->where('student_id', $studentId)->exists()) {
            return response()->json(['message' => 'Student is not in this group'], 404);
        }

        $group->students()->detach($studentId);

        return response()->json([
            'message' => 'Student removed from group successfully',
            'group'   => $this->formatGroup($group->load('students')),
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

        if (!$group->canManage(Auth::id())) {
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

        if (!$group->canManage(Auth::id())) {
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

        // Convert session_date + session_time from actor's timezone to UTC before storing
        $actorTz = Auth::user()->effective_timezone;
        $utcSession = TimezoneHelper::toUtc($request->session_date, $request->session_time, $actorTz);
        $sessionDateUtc = $utcSession->format('Y-m-d');
        $sessionTimeUtc = $utcSession->format('H:i');

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
                    'session_date' => $sessionDateUtc,
                    'session_time' => $sessionTimeUtc,
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
     * Join a group as an assistant teacher using a class code.
     * Teachers who enter a class code are added as assistant teachers, not as students.
     * The group owner cannot join their own group this way.
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

        $group = Group::with('assistantTeachers')->where('class_code', strtoupper($request->class_code))->first();

        if (!$group) {
            return response()->json(['message' => 'Invalid class code'], 404);
        }

        $teacherId = Auth::id();

        if ($group->group_teacher === $teacherId) {
            return response()->json(['message' => 'You are already the owner of this group'], 409);
        }

        if ($group->assistantTeachers()->where('teacher_id', $teacherId)->exists()) {
            return response()->json(['message' => 'You are already an assistant teacher in this group'], 409);
        }

        $group->assistantTeachers()->attach($teacherId);

        return response()->json([
            'message' => 'Joined group as assistant teacher successfully',
            'group'   => $this->formatGroup($group->load(['students', 'assistantTeachers'])),
        ]);
    }

    /**
     * Return attendance records for a group.
     * Only the group's teacher may view. Optionally filter by session_date.
     */
    public function getAttendance(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (!$group->canManage(Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $userTz = Auth::user()->effective_timezone;
        $query  = Attendance::where('group_id', $group->group_id);

        if ($request->filled('session_date')) {
            // Filter by the UTC date range that covers the requested local date
            $startUtc = Carbon::createFromFormat('Y-m-d H:i', $request->input('session_date') . ' 00:00', $userTz)->setTimezone('UTC');
            $endUtc   = Carbon::createFromFormat('Y-m-d H:i', $request->input('session_date') . ' 23:59', $userTz)->setTimezone('UTC');
            $query->whereBetween('session_date', [$startUtc->format('Y-m-d'), $endUtc->format('Y-m-d')]);
        }

        $records = $query->with('student:id,username,email,role')
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get()
            ->map(function ($a) use ($userTz) {
                $local = TimezoneHelper::fromUtc(
                    Carbon::createFromFormat('Y-m-d H:i', $a->session_date->format('Y-m-d') . ' ' . $a->session_time, 'UTC'),
                    $userTz
                );
                return [
                    'student_id'   => $a->student_id,
                    'username'     => $a->student?->username,
                    'email'        => $a->student?->email,
                    'session_date' => $local['date'],
                    'session_time' => $local['time'],
                    'status'       => $a->status,
                ];
            });

        return response()->json([
            'message'    => 'Attendance retrieved successfully',
            'group_id'   => $group->group_id,
            'group_name' => $group->group_name,
            'attendance' => $records,
        ]);
    }

    /**
     * Invite an assistant teacher to the group (by teacher ID).
     * Only the main teacher (group owner) can invite assistants.
     */
    public function addAssistantTeacher(Request $request, $id)
    {
        $group = Group::with('assistantTeachers')->find($id);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

        return response()->json([
            'message' => 'Assistant teacher added successfully',
            'group'   => $this->formatGroup($group->load(['students', 'assistantTeachers'])),
        ]);
    }

    /**
     * Remove an assistant teacher from the group.
     * Only the main teacher (group owner) can remove assistants.
     */
    public function removeAssistantTeacher($groupId, $teacherId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if ($group->group_teacher !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$group->assistantTeachers()->where('teacher_id', $teacherId)->exists()) {
            return response()->json(['message' => 'Teacher is not an assistant in this group'], 404);
        }

        $group->assistantTeachers()->detach($teacherId);

        return response()->json([
            'message' => 'Assistant teacher removed successfully',
            'group'   => $this->formatGroup($group->load(['students', 'assistantTeachers'])),
        ]);
    }
}
