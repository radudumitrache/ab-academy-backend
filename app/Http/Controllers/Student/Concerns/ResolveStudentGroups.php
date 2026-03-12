<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Group;

trait ResolveStudentGroups
{
    /**
     * Returns [groupIds, teacherIds] for all groups the student currently belongs to.
     */
    private function studentGroupContext(int $studentId): array
    {
        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id')
            ->get();

        $groupIds   = $groups->pluck('group_id')->toArray();
        $teacherIds = $groups->pluck('teacher.id')->filter()->unique()->values()->toArray();

        return [$groupIds, $teacherIds];
    }
}
