<?php

namespace App\Observers;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ProductAcquisition;
use App\Services\NotificationService;

class AttendanceObserver
{
    /**
     * Statuses that consume a course slot (present or unmotivated absence).
     */
    private const CONSUMING_STATUSES = ['present', 'absent'];

    public function created(Attendance $attendance): void
    {
        if (! in_array($attendance->status, self::CONSUMING_STATUSES, true)) {
            return;
        }

        // Find the active course acquisition for this student that covers this group
        $acquisition = ProductAcquisition::where('student_id', $attendance->student_id)
            ->whereIn('acquisition_status', ['active', 'paid'])
            ->whereNotNull('remaining_courses')
            ->whereJsonContains('groups_access', $attendance->group_id)
            ->first();

        if (! $acquisition || $acquisition->remaining_courses <= 0) {
            return;
        }

        $acquisition->decrement('remaining_courses');
        $acquisition->refresh();

        if ($acquisition->remaining_courses === 0) {
            $this->handleExhausted($acquisition);
        }
    }

    private function handleExhausted(ProductAcquisition $acquisition): void
    {
        $student    = $acquisition->student;
        $studentName = $student ? trim($student->first_name . ' ' . $student->last_name) : "Student #{$acquisition->student_id}";

        $message = "Student {$studentName} has used all course sessions for acquisition #{$acquisition->id}. "
            . "The student has been removed from the related group(s).";

        // Notify all admins
        $adminIds = Admin::pluck('id')->all();
        NotificationService::notify($adminIds, $message, 'Admin', 'Schedule');
        NotificationService::notifyByEmail($adminIds, $message, 'Schedule');

        // Remove student from the linked groups
        $acquisition->removeStudentFromGroups();
    }
}
