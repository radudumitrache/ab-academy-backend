<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    /**
     * List all events where the teacher is the organizer or is in the guest list.
     */
    public function index()
    {
        $teacherId = Auth::id();

        $events = Event::with('organizer')
            ->where(function ($query) use ($teacherId) {
                $query->where('event_organizer', $teacherId)
                      ->orWhereJsonContains('guests', $teacherId);
            })
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        return response()->json([
            'message' => 'Events retrieved successfully',
            'events'  => $events,
        ]);
    }

    /**
     * Show a single event — accessible if the teacher is organizer or invited.
     */
    public function show($id)
    {
        $teacherId = Auth::id();
        $event     = Event::with('organizer')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $isOrganizer = (int) $event->event_organizer === $teacherId;
        $guestIds    = collect($event->guests ?? [])->map(function ($guest) {
            return is_array($guest) ? ($guest['id'] ?? null) : $guest;
        })->filter()->map(fn ($g) => (int) $g)->all();
        $isInvited   = in_array($teacherId, $guestIds);

        if (!$isOrganizer && !$isInvited) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $event,
        ]);
    }

    /**
     * Create a new event. The authenticated teacher is automatically set as the organizer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'type'            => ['required', Rule::in(['class', 'meeting', 'other'])],
            'event_date'      => 'required|date',
            'event_time'      => 'required|date_format:H:i',
            'event_duration'  => 'required|integer|min:1',
            'guests'          => 'nullable|array',
            'guests.*'        => 'integer|exists:users,id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes'     => 'nullable|string',
        ]);

        $validated['event_organizer'] = Auth::id();

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully',
            'event'   => $event->load('organizer'),
        ], 201);
    }

    /**
     * Update an event — only the organizer (teacher who created it) may edit.
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if ((int) $event->event_organizer !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized — only the event organizer can edit this event'], 403);
        }

        $validated = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'type'            => ['sometimes', Rule::in(['class', 'meeting', 'other'])],
            'event_date'      => 'sometimes|date',
            'event_time'      => 'sometimes|date_format:H:i',
            'event_duration'  => 'sometimes|integer|min:1',
            'guests'          => 'nullable|array',
            'guests.*'        => 'integer|exists:users,id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes'     => 'nullable|string',
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $event->load('organizer'),
        ]);
    }

    /**
     * Delete an event — only the organizer (teacher who created it) may delete.
     */
    public function destroy($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if ((int) $event->event_organizer !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized — only the event organizer can delete this event'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
