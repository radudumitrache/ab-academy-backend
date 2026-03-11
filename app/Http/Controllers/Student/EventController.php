<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * List all events the authenticated student is invited to (appears in guests array).
     */
    public function index()
    {
        $studentId = Auth::id();

        $events = Event::whereJsonContains('guests', $studentId)
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
     * Show a single event. The student must be a guest.
     */
    public function show($id)
    {
        $studentId = Auth::id();

        $event = Event::whereJsonContains('guests', $studentId)
            ->with('organizer:id,username')
            ->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $this->format($event),
        ]);
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
