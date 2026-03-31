<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupAnnouncementController extends Controller
{
    public function index()
    {
        $teacherId = Auth::id();

        $groupIds = Group::all()->filter(fn($g) => $g->canManage($teacherId))->pluck('group_id');

        $announcements = GroupAnnouncement::with('group:group_id,group_name')
            ->whereIn('group_id', $groupIds)
            ->orderByDesc('time_created')
            ->get();

        return response()->json([
            'message'       => 'Announcements retrieved successfully',
            'count'         => $announcements->count(),
            'announcements' => $announcements,
        ]);
    }

    public function show($id)
    {
        $teacherId = Auth::id();
        $announcement = GroupAnnouncement::with('group:group_id,group_name')->find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        if (!$announcement->group || !$announcement->group->canManage($teacherId)) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        return response()->json([
            'message'      => 'Announcement retrieved successfully',
            'announcement' => $announcement,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'group_id'         => 'required|integer|exists:groups,group_id',
            'message'          => 'required|string',
            'attached_files'   => 'nullable|array',
            'attached_files.*' => 'integer|exists:materials,material_id',
        ]);

        $group = Group::find($validated['group_id']);

        if (!$group || !$group->canManage(Auth::id())) {
            return response()->json(['message' => 'You are not authorised to post announcements in this group'], 403);
        }

        $announcement = GroupAnnouncement::create($validated);

        return response()->json([
            'message'      => 'Announcement created successfully',
            'announcement' => $announcement,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $teacherId = Auth::id();
        $announcement = GroupAnnouncement::with('group')->find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        if (!$announcement->group || !$announcement->group->canManage($teacherId)) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'group_id'         => 'sometimes|integer|exists:groups,group_id',
            'message'          => 'sometimes|string',
            'attached_files'   => 'nullable|array',
            'attached_files.*' => 'integer|exists:materials,material_id',
        ]);

        if (isset($validated['group_id']) && $validated['group_id'] !== $announcement->group_id) {
            $newGroup = Group::find($validated['group_id']);
            if (!$newGroup || !$newGroup->canManage($teacherId)) {
                return response()->json(['message' => 'You are not authorised to move this announcement to the specified group'], 403);
            }
        }

        $announcement->update($validated);

        return response()->json([
            'message'      => 'Announcement updated successfully',
            'announcement' => $announcement->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $teacherId = Auth::id();
        $announcement = GroupAnnouncement::with('group')->find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        if (!$announcement->group || !$announcement->group->canManage($teacherId)) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully']);
    }
}
