<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
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
     * Show a single event — accessible to any authenticated teacher.
     * Resolves guest IDs to full user objects.
     */
    public function show($id)
    {
        $event = Event::with('organizer')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $guestIds = collect($event->guests ?? [])->map(function ($guest) {
            return is_array($guest) ? ($guest['id'] ?? null) : $guest;
        })->filter()->map(fn ($g) => (int) $g)->unique()->values()->toArray();

        $guestUsers = User::whereIn('id', $guestIds)
            ->select('id', 'username', 'email', 'role')
            ->get();

        return response()->json([
            'message'     => 'Event retrieved successfully',
            'event'       => $event,
            'guest_users' => $guestUsers,
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
     * Record which guests were actually present at the event.
     * Only the organizer may mark attendance.
     * Accepts a list of user IDs — they must all be in the event's guest list.
     */
    public function markAttendance(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if ((int) $event->event_organizer !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized — only the event organizer can mark attendance'], 403);
        }

        $validated = $request->validate([
            'present_guest_ids'   => 'required|array',
            'present_guest_ids.*' => 'integer|exists:users,id',
        ]);

        $guestIds = collect($event->guests ?? [])->map(function ($guest) {
            return is_array($guest) ? ($guest['id'] ?? null) : $guest;
        })->filter()->map(fn ($g) => (int) $g)->toArray();

        $presentIds = array_values(array_unique($validated['present_guest_ids']));
        $unauthorized = array_diff($presentIds, $guestIds);

        if (!empty($unauthorized)) {
            return response()->json([
                'message'              => 'Some users are not on the guest list for this event',
                'not_on_guest_list'    => array_values($unauthorized),
            ], 422);
        }

        $event->update(['present_guests' => $presentIds]);

        $presentUsers = User::whereIn('id', $presentIds)
            ->select('id', 'username', 'email', 'role')
            ->get();

        return response()->json([
            'message'        => 'Attendance recorded successfully',
            'present_guests' => $presentIds,
            'present_users'  => $presentUsers,
        ]);
    }

    /**
     * Add guests to an event by their usernames.
     * Only the organizer may add guests.
     * Already-present guests are silently skipped (no duplicates).
     */
    public function addGuestsByUsername(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if ((int) $event->event_organizer !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized — only the event organizer can add guests'], 403);
        }

        $validated = $request->validate([
            'usernames'   => 'required|array|min:1',
            'usernames.*' => 'string',
        ]);

        $users = User::whereIn('username', $validated['usernames'])->get(['id', 'username', 'email', 'role']);

        $foundUsernames   = $users->pluck('username')->toArray();
        $unknownUsernames = array_values(array_diff($validated['usernames'], $foundUsernames));

        if (!empty($unknownUsernames)) {
            return response()->json([
                'message'           => 'Some usernames were not found',
                'unknown_usernames' => $unknownUsernames,
            ], 422);
        }

        $existingGuestIds = collect($event->guests ?? [])->map(function ($guest) {
            return is_array($guest) ? ($guest['id'] ?? null) : $guest;
        })->filter()->map(fn ($g) => (int) $g)->toArray();

        $newIds    = $users->pluck('id')->map(fn ($g) => (int) $g)->toArray();
        $mergedIds = array_values(array_unique(array_merge($existingGuestIds, $newIds)));

        $event->update(['guests' => $mergedIds]);

        $allGuestUsers = User::whereIn('id', $mergedIds)
            ->select('id', 'username', 'email', 'role')
            ->get();

        return response()->json([
            'message'     => 'Guests added successfully',
            'guests'      => $mergedIds,
            'guest_users' => $allGuestUsers,
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
