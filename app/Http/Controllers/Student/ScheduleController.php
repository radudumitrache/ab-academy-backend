<?php

namespace App\Http\Controllers\Student;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolveStudentGroups;
use App\Models\Event;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    use ResolveStudentGroups;

    /**
     * Return the schedule overview for all groups the student belongs to,
     * plus upcoming events the student has access to.
     *
     * Event access is granted if:
     *  - the student is directly in `guests`, or
     *  - any of their groups is in `guest_groups`, or
     *  - the event organizer is the teacher of one of their groups
     *    (covers events scheduled before the student joined the group).
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

        $groupIds = $groups->pluck('group_id')->toArray();

        $events = Event::where(function ($q) use ($studentId, $groupIds) {
                $q->whereJsonContains('guests', $studentId);
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('guest_groups', $gid);
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
        $utcCarbon = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );
        $local = TimezoneHelper::fromUtc($utcCarbon, Auth::user()->effective_timezone);

        return [
            'id'               => $event->id,
            'title'            => $event->title,
            'type'             => $event->type,
            'event_date'       => $local['date'],
            'event_time'       => $local['time'],
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
