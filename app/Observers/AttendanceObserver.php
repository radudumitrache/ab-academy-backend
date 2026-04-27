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

        // Find the active course acquisition for this student that covers this group.
        // Prefer an acquisition directly tied via group_id; fall back to groups_access JSON for older records.
        $acquisition = ProductAcquisition::where('student_id', $attendance->student_id)
            ->whereIn('acquisition_status', ['active', 'paid'])
            ->whereNotNull('remaining_courses')
            ->where(function ($q) use ($attendance) {
                $q->where('group_id', $attendance->group_id)
                  ->orWhere(function ($q2) use ($attendance) {
                      $q2->whereNull('group_id')
                         ->whereJsonContains('groups_access', $attendance->group_id);
                  });
            })
            ->orderByRaw('group_id IS NOT NULL DESC')
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
        $student     = $acquisition->student;
        $studentName = $student ? trim($student->first_name . ' ' . $student->last_name) : "Student #{$acquisition->student_id}";
        $studentId   = $acquisition->student_id;
        $productName = $acquisition->product?->name ?? "acquisition #{$acquisition->id}";

        $adminMessage = "Student {$studentName} (ID: {$studentId}) has used all course sessions for \"{$productName}\". "
            . "The student has been removed from the related group(s).";

        // Notify all admins
        $adminIds = Admin::pluck('id')->all();
        NotificationService::notify($adminIds, $adminMessage, 'Admin', 'Schedule');
        NotificationService::notifyByEmail($adminIds, $adminMessage, 'Schedule');

        // Notify the student
        if ($student) {
            $studentMessage = "You have used all your course sessions for your current plan. "
                . "Please contact us to renew your subscription.";
            NotificationService::notify($student->id, $studentMessage, 'Admin', 'Schedule');
        }

        // Remove student from the linked groups
        $acquisition->removeStudentFromGroups();
    }
}
