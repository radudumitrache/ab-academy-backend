<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAnnouncement;
use Illuminate\Support\Facades\Auth;

class GroupAnnouncementController extends Controller
{
    public function getGroupAnnouncements(int $groupId)
    {
        $group = Group::whereHas('students', fn($q) => $q->where('student_id', Auth::id()))
            ->where('group_id', $groupId)
            ->first();

        if (!$group) {
            return response()->json(['message' => 'You are not a member of this group'], 403);
        }

        $announcements = GroupAnnouncement::where('group_id', $groupId)
            ->orderByDesc('time_created')
            ->get();

        return response()->json([
            'message'       => 'Announcements retrieved successfully',
            'count'         => $announcements->count(),
            'announcements' => $announcements,
        ]);
    }
}
