<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\GroupAnnouncement;
use Illuminate\Http\Request;

class GroupAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = GroupAnnouncement::with('group:group_id,group_name')
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
        $announcement = GroupAnnouncement::with('group:group_id,group_name')->find($id);

        if (!$announcement) {
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
            'title'              => 'required|string|max:255',
            'group_id'           => 'required|integer|exists:groups,group_id',
            'message'            => 'required|string',
            'attached_files'     => 'nullable|array',
            'attached_files.*'   => 'integer|exists:materials,material_id',
        ]);

        $announcement = GroupAnnouncement::create($validated);

        DatabaseLog::logAction(
            'create',
            GroupAnnouncement::class,
            $announcement->announcement_id,
            "Created group announcement '{$announcement->title}' for group {$announcement->group_id}"
        );

        return response()->json([
            'message'      => 'Announcement created successfully',
            'announcement' => $announcement,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $announcement = GroupAnnouncement::find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'group_id'         => 'sometimes|integer|exists:groups,group_id',
            'message'          => 'sometimes|string',
            'attached_files'   => 'nullable|array',
            'attached_files.*' => 'integer|exists:materials,material_id',
        ]);

        $announcement->update($validated);

        DatabaseLog::logAction(
            'update',
            GroupAnnouncement::class,
            $announcement->announcement_id,
            "Updated group announcement '{$announcement->title}'"
        );

        return response()->json([
            'message'      => 'Announcement updated successfully',
            'announcement' => $announcement->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $announcement = GroupAnnouncement::find($id);

        if (!$announcement) {
            return response()->json(['message' => 'Announcement not found'], 404);
        }

        $announcementId = $announcement->announcement_id;
        $title = $announcement->title;
        $announcement->delete();

        DatabaseLog::logAction(
            'delete',
            GroupAnnouncement::class,
            $announcementId,
            "Deleted group announcement '{$title}'"
        );

        return response()->json(['message' => 'Announcement deleted successfully']);
    }
}
