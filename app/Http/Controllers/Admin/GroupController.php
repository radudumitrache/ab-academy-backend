<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
use App\Models\DatabaseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of all groups.
     */
    public function index()
    {
        $groups = Group::with(['teacher', 'students'])->get();

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups' => $groups
        ], 200);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string|max:255',
            'group_teacher' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'normal_schedule' => 'required|date',
            'group_members' => 'nullable|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->except('group_members');
        $group = Group::create($payload);

        if ($request->filled('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members));
            $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return response()->json([
                    'message' => 'All group_members must be valid students',
                ], 422);
            }

            $group->students()->sync($studentIds);
        }

        DatabaseLog::logAction(
            'created',
            'Group',
            $group->group_id,
            auth()->id(),
            auth()->user()->role,
            'Admin created a new group',
            $group->toArray()
        );

        return response()->json([
            'message' => 'Group created successfully',
            'group' => $group->load(['teacher', 'students'])
        ], 201);
    }

    /**
     * Display the specified group.
     */
    public function show($id)
    {
        $group = Group::with(['teacher', 'students'])->find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group' => $group
        ], 200);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_name' => 'sometimes|string|max:255',
            'group_teacher' => 'sometimes|exists:users,id',
            'description' => 'nullable|string',
            'normal_schedule' => 'sometimes|date',
            'group_members' => 'sometimes|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $group->toArray();
        $payload = $request->except('group_members');
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

        DatabaseLog::logAction(
            'updated',
            'Group',
            $group->group_id,
            auth()->id(),
            auth()->user()->role,
            'Admin updated group',
            ['old' => $oldData, 'new' => $group->toArray()]
        );

        return response()->json([
            'message' => 'Group updated successfully',
            'group' => $group->load(['teacher', 'students'])
        ], 200);
    }

    /**
     * Remove the specified group.
     */
    public function destroy($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        $groupData = $group->toArray();
        $group->delete();

        DatabaseLog::logAction(
            'deleted',
            'Group',
            $id,
            auth()->id(),
            auth()->user()->role,
            'Admin deleted group',
            $groupData
        );

        return response()->json([
            'message' => 'Group deleted successfully'
        ], 200);
    }

    /**
     * Add a student to a group.
     */
    public function addStudent(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::find($request->student_id);

        if (!$student) {
            return response()->json([
                'message' => 'Student not found or user is not a student'
            ], 404);
        }

        // Check if student is already in the group
        if ($group->students()->where('student_id', $request->student_id)->exists()) {
            return response()->json([
                'message' => 'Student is already in this group'
            ], 409);
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

        return response()->json([
            'message' => 'Student added to group successfully',
            'group' => $group->load(['teacher', 'students'])
        ], 200);
    }

    /**
     * Remove a student from a group.
     */
    public function removeStudent($groupId, $studentId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        if (!$group->students()->where('student_id', $studentId)->exists()) {
            return response()->json([
                'message' => 'Student is not in this group'
            ], 404);
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

        return response()->json([
            'message' => 'Student removed from group successfully',
            'group' => $group->load(['teacher', 'students'])
        ], 200);
    }

    /**
     * Update group members for a group.
     */
    public function updateGroupMembers(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'group_members' => 'required|array',
            'group_members.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $requestedMembers = array_values(array_unique($request->group_members));
        $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

        if (count($studentIds) !== count($requestedMembers)) {
            return response()->json([
                'message' => 'All group_members must be valid students',
            ], 422);
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

        return response()->json([
            'message' => 'Group members updated successfully',
            'group' => $group->load(['teacher', 'students'])
        ], 200);
    }
}
