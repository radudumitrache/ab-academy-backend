<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Return the schedule overview for all groups the student belongs to,
     * plus upcoming events the student is invited to.
     */
    public function index()
    {
        $studentId = Auth::id();

        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id,username')
            ->get();

        $schedule = $groups->map(function ($group) {
            return [
                'group_id'             => $group->group_id,
                'group_name'           => $group->group_name,
                'description'          => $group->description,
                'teacher'              => $group->teacher ? [
                    'id'       => $group->teacher->id,
                    'username' => $group->teacher->username,
                ] : null,
                'schedule_days'        => $group->schedule_days ?? [],
                'formatted_schedule'   => $group->formatted_schedule,
                'total_weekly_minutes' => $group->total_weekly_minutes,
            ];
        });

        // Collect teacher IDs from the student's groups
        $teacherIds = $groups->pluck('teacher.id')->filter()->unique()->values()->toArray();

        $events = Event::where(function ($q) use ($studentId, $teacherIds) {
                $q->whereJsonContains('guests', $studentId);
                foreach ($teacherIds as $tid) {
                    $q->orWhere('event_organizer', $tid);
                }
            })
            ->where('event_date', '>=', now()->toDateString())
            ->with('organizer:id,username')
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->unique('id')
            ->map(fn($e) => $this->formatEvent($e))
            ->values();

        return response()->json([
            'message'  => 'Schedule retrieved successfully',
            'schedule' => $schedule,
            'events'   => $events,
        ]);
    }

    private function formatEvent(Event $event): array
    {
        return [
            'id'               => $event->id,
            'title'            => $event->title,
            'type'             => $event->type,
            'event_date'       => $event->event_date,
            'event_time'       => $event->event_time,
            'event_duration'   => $event->event_duration,
            'event_meet_link'  => $event->event_meet_link,
            'event_notes'      => $event->event_notes,
            'organizer'        => $event->organizer ? [
                'id'       => $event->organizer->id,
                'username' => $event->organizer->username,
            ] : null,
        ];
    }
}
