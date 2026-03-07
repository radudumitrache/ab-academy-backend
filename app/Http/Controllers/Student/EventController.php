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
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

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

        $event = Event::whereJsonContains('guests', $studentId)->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $event,
        ]);
    }
}
