<?php

namespace App\Observers;

use App\Models\Exam;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ExamObserver
{
    public function created(Exam $exam): void
    {
        $this->notifyParticipants(
            $exam,
            "A new exam '{$exam->name}' has been scheduled for {$exam->date->format('Y-m-d')}."
        );
    }

    public function updated(Exam $exam): void
    {
        $this->notifyParticipants(
            $exam,
            "The exam '{$exam->name}' has been updated. Date: {$exam->date->format('Y-m-d')}, Status: {$exam->status}."
        );
    }

    public function deleting(Exam $exam): void
    {
        $this->notifyParticipants(
            $exam,
            "The exam '{$exam->name}' has been cancelled."
        );
    }

    private function notifyParticipants(Exam $exam, string $message): void
    {
        // Enrolled students
        $studentIds = $exam->students()->pluck('users.id')->toArray();

        if (empty($studentIds)) {
            return;
        }

        // Teachers of any group that contains at least one enrolled student
        $teacherIds = DB::table('group_student')
            ->join('groups', 'group_student.group_id', '=', 'groups.group_id')
            ->whereIn('group_student.student_id', $studentIds)
            ->whereNull('groups.deleted_at')
            ->pluck('groups.group_teacher')
            ->filter()
            ->unique()
            ->toArray();

        $allIds = array_unique(array_merge($studentIds, $teacherIds));

        NotificationService::notify($allIds, $message, 'Admin', 'Exam');
    }
}
