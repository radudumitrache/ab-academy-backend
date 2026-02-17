<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use App\Models\Group;
use App\Models\Student;
use App\Models\DatabaseLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    use ApiResponder;

    /**
     * Display a listing of all groups.
     */
    public function index()
    {
        $groups = Group::with(['teacher', 'students'])->get();

        return $this->success($groups, 'Groups retrieved successfully');
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
            return $this->validationError($validator->errors());
        }

        $payload = $request->except('group_members');
        $group = Group::create($payload);

        if ($request->filled('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members));
            $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return $this->error('All group_members must be valid students', 422);
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

        return $this->success($group->load(['teacher', 'students']), 'Group created successfully', 201);
    }

    /**
     * Display the specified group.
     */
    public function show($id)
    {
        $group = Group::with(['teacher', 'students'])->find($id);

        if (!$group) {
            return $this->notFound('Group not found');
        }

        return $this->success($group, 'Group retrieved successfully');
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, $id)
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->notFound('Group not found');
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
            return $this->validationError($validator->errors());
        }

        $oldData = $group->toArray();
        $payload = $request->except('group_members');
        $group->update($payload);

        if ($request->has('group_members')) {
            $requestedMembers = array_values(array_unique($request->group_members ?? []));
            $studentIds = Student::whereIn('id', $requestedMembers)->pluck('id')->all();

            if (count($studentIds) !== count($requestedMembers)) {
                return $this->error('All group_members must be valid students', 422);
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

        return $this->success($group->load(['teacher', 'students']), 'Group updated successfully');
    }

    /**
     * Remove the specified group.
     */
    public function destroy($id)
    {
        $group = Group::find($id);

        if (!$group) {
            return $this->notFound('Group not found');
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

        return $this->success(null, 'Group deleted successfully');
    }
}
