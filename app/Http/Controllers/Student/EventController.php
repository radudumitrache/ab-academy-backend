<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * List all events the authenticated student is invited to:
     * - directly (their ID appears in `guests`), or
     * - via group membership (any of their groups appears in `guest_groups`).
     */
    public function index()
    {
        $studentId = Auth::id();
        $groupIds  = $this->studentGroupIds($studentId);

        $events = Event::where(function ($q) use ($studentId, $groupIds) {
                $q->whereJsonContains('guests', $studentId);
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('guest_groups', $gid);
                }
            })
            ->with('organizer:id,username')
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->map(fn($e) => $this->format($e));

        return response()->json([
            'message' => 'Events retrieved successfully',
            'count'   => $events->count(),
            'events'  => $events,
        ]);
    }

    /**
     * Show a single event. The student must be directly invited or a member of an invited group.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        $groupIds  = $this->studentGroupIds($studentId);

        $event = Event::with('organizer:id,username')->find($id);

        if (!$event || !$this->studentHasAccess($event, $studentId, $groupIds)) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $this->format($event),
        ]);
    }

    private function studentGroupIds(int $studentId): array
    {
        return Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();
    }

    private function studentHasAccess(Event $event, int $studentId, array $groupIds): bool
    {
        if (in_array($studentId, $event->guests ?? [])) {
            return true;
        }
        foreach ($groupIds as $gid) {
            if (in_array($gid, $event->guest_groups ?? [])) {
                return true;
            }
        }
        return false;
    }

    private function format(Event $event): array
    {
        return [
            'id'              => $event->id,
            'title'           => $event->title,
            'type'            => $event->type,
            'event_date'      => $event->event_date,
            'event_time'      => $event->event_time,
            'event_duration'  => $event->event_duration,
            'event_meet_link' => $event->event_meet_link,
            'event_notes'     => $event->event_notes,
            'organizer'       => $event->organizer ? [
                'id'       => $event->organizer->id,
                'username' => $event->organizer->username,
            ] : null,
        ];
    }
}
