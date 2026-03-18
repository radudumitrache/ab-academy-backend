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
        $recipients = $this->resolveRecipients($homework);
        $message = "New homework '{$homework->homework_title}' has been assigned, due on {$due}.";

        NotificationService::notify($recipients, $message, 'Admin', 'Homework');
        NotificationService::notifyByEmail($recipients, $message, 'Homework');
    }

    public function updated(Homework $homework): void
    {
        $recipients = $this->resolveRecipients($homework);
        $message = "Homework '{$homework->homework_title}' has been updated.";

        NotificationService::notify($recipients, $message, 'Admin', 'Homework');
        NotificationService::notifyByEmail($recipients, $message, 'Homework');
    }

    public function deleting(Homework $homework): void
    {
        $recipients = $this->resolveRecipients($homework);
        $message = "Homework '{$homework->homework_title}' has been removed.";

        NotificationService::notify($recipients, $message, 'Admin', 'Homework');
        NotificationService::notifyByEmail($recipients, $message, 'Homework');
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
