<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    use ApiResponder;

    /**
     * Display a listing of the events.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $events = Event::with('organizer')->orderBy('event_date')->orderBy('event_time')->get();
        return $this->success($events, 'Events retrieved successfully');
    }

    /**
     * Store a newly created event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin', 'student'], true)) {
            return $this->forbidden('Only admin or student can create events.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['class', 'meeting', 'other'])],
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'event_duration' => 'required|integer|min:1',
            'event_organizer' => ['nullable', Rule::exists('users', 'id')],
            'guests' => 'nullable|array',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        if ($user->role === 'student') {
            $validated['event_organizer'] = $user->id;
        } elseif (empty($validated['event_organizer'])) {
            $validated['event_organizer'] = $user->id;
        }

        $event = Event::create($validated);

        return $this->success($event->load('organizer'), 'Event created successfully.', 201);
    }

    /**
     * Display the specified event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin', 'teacher', 'student'], true)) {
            return $this->forbidden('You are not allowed to view events.');
        }

        $event = Event::with('organizer')->find($id);

        if (!$event) {
            return $this->notFound('Event not found.');
        }

        if ($user->role === 'student') {
            $guestIds = collect($event->guests ?? [])
                ->map(function ($guest) {
                    if (is_array($guest)) {
                        return $guest['id'] ?? null;
                    }

                    return $guest;
                })
                ->filter()
                ->map(fn ($guestId) => (string) $guestId)
                ->all();

            if (!in_array((string) $user->id, $guestIds, true)) {
                return $this->forbidden('Students can only view events they are invited to.');
            }
        }

        return $this->success($event, 'Event retrieved successfully.');
    }

    /**
     * Update the specified event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $event = Event::find($id);

        if (!$event) {
            return $this->notFound('Event not found.');
        }

        $isAdmin = $user && $user->role === 'admin';
        $isOrganizer = $user && (int) $event->event_organizer === (int) $user->id;

        if (!$isAdmin && !$isOrganizer) {
            return $this->forbidden('Only the event organizer or admin can edit this event.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['class', 'meeting', 'other'])],
            'event_date' => 'sometimes|date',
            'event_time' => 'sometimes|date_format:H:i',
            'event_duration' => 'sometimes|integer|min:1',
            'event_organizer' => ['sometimes', Rule::exists('users', 'id')],
            'guests' => 'nullable|array',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        if (!$isAdmin && array_key_exists('event_organizer', $validated)) {
            return $this->forbidden('Only admin can change the event organizer.');
        }

        $event->update($validated);

        return $this->success($event->load('organizer'), 'Event updated successfully.');
    }

    /**
     * Remove the specified event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $event = Event::find($id);

        if (!$event) {
            return $this->notFound('Event not found.');
        }

        $isAdmin = $user && $user->role === 'admin';
        $isOrganizer = $user && (int) $event->event_organizer === (int) $user->id;

        if (!$isAdmin && !$isOrganizer) {
            return $this->forbidden('Only the event organizer or admin can delete this event.');
        }

        $event->delete();

        return $this->success(null, 'Event deleted successfully.');
    }
}
