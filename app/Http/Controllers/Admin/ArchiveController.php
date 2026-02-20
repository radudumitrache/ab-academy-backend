<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    /**
     * Display a listing of archived courses.
     */
    public function archivedCourses()
    {
        $courses = Course::onlyTrashed()->with('teacher')->get();

        return response()->json([
            'message' => 'Archived courses retrieved successfully',
            'count' => $courses->count(),
            'courses' => $courses
        ], 200);
    }

    /**
     * Display a listing of archived groups.
     */
    public function archivedGroups()
    {
        $groups = Group::onlyTrashed()->with(['teacher'])->get();

        return response()->json([
            'message' => 'Archived groups retrieved successfully',
            'count' => $groups->count(),
            'groups' => $groups
        ], 200);
    }

    /**
     * Restore an archived course.
     */
    public function restoreCourse($id)
    {
        $course = Course::onlyTrashed()->find($id);

        if (!$course) {
            return response()->json([
                'message' => 'Archived course not found'
            ], 404);
        }

        $course->restore();

        return response()->json([
            'message' => 'Course restored successfully',
            'course' => $course->fresh(['teacher'])
        ], 200);
    }

    /**
     * Restore an archived group.
     */
    public function restoreGroup($id)
    {
        $group = Group::onlyTrashed()->find($id);

        if (!$group) {
            return response()->json([
                'message' => 'Archived group not found'
            ], 404);
        }

        $group->restore();

        return response()->json([
            'message' => 'Group restored successfully',
            'group' => $group->fresh(['teacher', 'students'])
        ], 200);
    }
}
