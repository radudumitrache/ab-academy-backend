<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolveStudentGroups;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    use ResolveStudentGroups;

    /**
     * List all events the authenticated student has access to:
     * - directly (their ID appears in `guests`), or
     * - via group invite (any of their groups appears in `guest_groups`), or
     * - via group teacher (event organizer is the teacher of one of their groups).
     *   This ensures students who join a group after events are scheduled still see them.
     */
    public function index()
    {
        $studentId = Auth::id();
        [$groupIds, $teacherIds] = $this->studentGroupContext($studentId);

        $events = Event::where(function ($q) use ($studentId, $groupIds, $teacherIds) {
                $q->whereJsonContains('guests', $studentId);
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('guest_groups', $gid);
                }
                if (!empty($teacherIds)) {
                    $q->orWhereIn('event_organizer', $teacherIds);
                }
            })
            ->with('organizer:id,username')
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->unique('id')
            ->map(fn($e) => $this->format($e))
            ->values();

        return response()->json([
            'message' => 'Events retrieved successfully',
            'count'   => $events->count(),
            'events'  => $events,
        ]);
    }

    /**
     * Show a single event. Access is granted via direct invite, group invite, or group teacher.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        [$groupIds, $teacherIds] = $this->studentGroupContext($studentId);

        $event = Event::with('organizer:id,username')->find($id);

        if (!$event || !$this->studentHasAccess($event, $studentId, $groupIds, $teacherIds)) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $this->format($event),
        ]);
    }

    private function studentHasAccess(Event $event, int $studentId, array $groupIds, array $teacherIds): bool
    {
        if (in_array($studentId, $event->guests ?? [])) {
            return true;
        }
        foreach ($groupIds as $gid) {
            if (in_array($gid, $event->guest_groups ?? [])) {
                return true;
            }
        }
        if (in_array((int) $event->event_organizer, $teacherIds)) {
            return true;
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
