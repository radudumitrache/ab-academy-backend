<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use App\Models\Group;
use App\Models\Student;
use App\Models\DatabaseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupMemberController extends Controller
{
    use ApiResponder;

    /**
     * Add a student to a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addStudent(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->notFound('Group not found');
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $student = Student::find($request->student_id);

        if (!$student) {
            return $this->notFound('Student not found or user is not a student');
        }

        // Check if student is already in the group
        if ($group->students()->where('student_id', $request->student_id)->exists()) {
            return $this->error('Student is already in this group', 409);
        }

        $group->students()->attach($request->student_id);

        DatabaseLog::logAction(
            'updated',
            'Group',
            $group->group_id,
            auth()->id(),
            auth()->user()->role,
            'Admin added student to group',
            ['group_id' => $group->group_id, 'student_id' => $request->student_id]
        );

        return $this->success($group->load(['teacher', 'students']), 'Student added to group successfully');
    }

    /**
     * Remove a student from a group.
     *
     * @param  int  $groupId
     * @param  int  $studentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeStudent($groupId, $studentId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return $this->notFound('Group not found');
        }

        if (!$group->students()->where('student_id', $studentId)->exists()) {
            return $this->notFound('Student is not in this group');
        }

        $group->students()->detach($studentId);

        DatabaseLog::logAction(
            'updated',
            'Group',
            $group->group_id,
            auth()->id(),
            auth()->user()->role,
            'Admin removed student from group',
            ['group_id' => $group->group_id, 'student_id' => $studentId]
        );

        return $this->success($group->load(['teacher', 'students']), 'Student removed from group successfully');
    }

    /**
     * Update group members for a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroupMembers(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->notFound('Group not found');
        }

        $validator = Validator::make($request->all(), [
            'group_members' => 'required|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $requestedMembers = array_values(array_unique($request->group_members));
        $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

        if (count($studentIds) !== count($requestedMembers)) {
            return $this->error('All group_members must be valid students', 422);
        }

        $oldMembers = $group->students()->pluck('users.id')->all();
        $group->students()->sync($studentIds);
        $newMembers = $group->students()->pluck('users.id')->all();

        DatabaseLog::logAction(
            'updated',
            'Group',
            $group->group_id,
            auth()->id(),
            auth()->user()->role,
            'Admin updated group members',
            ['old' => $oldMembers, 'new' => $newMembers]
        );

        return $this->success($group->load(['teacher', 'students']), 'Group members updated successfully');
    }
}
