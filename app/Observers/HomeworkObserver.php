<?php

namespace App\Observers;

use App\Models\Homework;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class HomeworkObserver
{
    public function created(Homework $homework): void
    {
        $due = $homework->due_date ? $homework->due_date->format('Y-m-d') : 'TBD';

        NotificationService::notify(
            $this->resolveRecipients($homework),
            "New homework '{$homework->homework_title}' has been assigned, due on {$due}.",
            'Admin',
            'Homework'
        );
    }

    public function updated(Homework $homework): void
    {
        NotificationService::notify(
            $this->resolveRecipients($homework),
            "Homework '{$homework->homework_title}' has been updated.",
            'Admin',
            'Homework'
        );
    }

    public function deleting(Homework $homework): void
    {
        NotificationService::notify(
            $this->resolveRecipients($homework),
            "Homework '{$homework->homework_title}' has been removed.",
            'Admin',
            'Homework'
        );
    }

    private function resolveRecipients(Homework $homework): array
    {
        $ids = $homework->people_assigned ?? [];

        // Add all students from assigned groups
        if (!empty($homework->groups_assigned)) {
            $groupStudentIds = DB::table('group_student')
                ->whereIn('group_id', $homework->groups_assigned)
                ->pluck('student_id')
                ->toArray();

            $ids = array_merge($ids, $groupStudentIds);
        }

        return array_unique(array_filter($ids));
    }
}
