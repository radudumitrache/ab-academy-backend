<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\MeetingAccount;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('organizer')->orderBy('event_date')->orderBy('event_time')->get();

        return response()->json([
            'message' => 'Events retrieved successfully',
            'events' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['class', 'meeting', 'other'])],
            'event_date' => 'required|date',
            'event_time' => 'required|date_format:H:i',
            'event_duration' => 'required|integer|min:1',
            'event_organizer' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['teacher', 'admin']);
                }),
            ],
            'guests' => 'nullable|array',
            'guests.*' => 'integer|exists:users,id',
            'guest_groups' => 'nullable|array',
            'guest_groups.*' => 'integer|exists:groups,id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event->load('organizer'),
        ], 201);
    }

    public function show($id)
    {
        $event = Event::with('organizer')->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event' => $event,
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['class', 'meeting', 'other'])],
            'event_date' => 'sometimes|date',
            'event_time' => 'sometimes|date_format:H:i',
            'event_duration' => 'sometimes|integer|min:1',
            'event_organizer' => [
                'sometimes',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['teacher', 'admin']);
                }),
            ],
            'guests' => 'nullable|array',
            'guests.*' => 'integer|exists:users,id',
            'guest_groups' => 'nullable|array',
            'guest_groups.*' => 'integer|exists:groups,id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event->load('organizer'),
        ]);
    }

    public function destroy($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
            ], 404);
        }

        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
        ]);
    }

    /**
     * Automatically select an available meeting account and create a Zoom meeting for the event.
     * Admins can do this for any event.
     */
    public function createZoomMeeting(ZoomService $zoom, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $eventStart = strtotime($event->event_date->format('Y-m-d') . ' ' . $event->event_time);
        $eventEnd   = $eventStart + ($event->event_duration * 60);

        $busyAccountIds = Event::whereDate('event_date', $event->event_date)
            ->whereNotNull('meeting_account_id')
            ->where('id', '!=', $event->id)
            ->get()
            ->filter(function ($other) use ($eventStart, $eventEnd) {
                $otherStart = strtotime($other->event_date->format('Y-m-d') . ' ' . $other->event_time);
                $otherEnd   = $otherStart + ($other->event_duration * 60);
                return $otherStart < $eventEnd && $otherEnd > $eventStart;
            })
            ->pluck('meeting_account_id')
            ->unique()
            ->toArray();

        $account = MeetingAccount::where('is_active', true)
            ->whereNotIn('id', $busyAccountIds)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'No available meeting accounts for this time slot'], 422);
        }

        try {
            $urls = $zoom->createMeeting($account, $event);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Zoom meeting creation failed: ' . $e->getMessage()], 502);
        }

        $event->update([
            'meeting_account_id' => $account->id,
            'event_meet_link'    => $urls['join_url'],
            'event_start_link'   => $urls['start_url'],
        ]);

        $event->refresh();

        return response()->json([
            'message'      => 'Zoom meeting created successfully',
            'event'        => $event->load('organizer'),
            'meeting_link' => $urls['join_url'],
            'start_link'   => $urls['start_url'],
        ]);
    }

    /**
     * Create recurring copies of an event for the rest of the current month.
     *
     * POST /api/admin/events/{id}/recur-monthly
     *
     * Body (optional):
     *   interval_weeks (int, default 1) — how many weeks between occurrences
     *   create_zoom    (bool, default false) — auto-create a Zoom meeting for each copy
     */
    public function recurMonthly(Request $request, ZoomService $zoom, $id)
    {
        $event = Event::with('organizer')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $validated = $request->validate([
            'interval_weeks' => 'sometimes|integer|min:1|max:4',
            'create_zoom'    => 'sometimes|boolean',
        ]);

        $intervalWeeks = $validated['interval_weeks'] ?? 1;
        $createZoom    = $validated['create_zoom'] ?? false;

        $baseDate  = Carbon::parse($event->event_date);
        $endOfMonth = $baseDate->copy()->endOfMonth();

        $created = [];
        $current = $baseDate->copy()->addWeeks($intervalWeeks);

        while ($current->lte($endOfMonth)) {
            $copyData = $event->only([
                'title', 'type', 'event_time', 'event_duration',
                'event_organizer', 'guests', 'guest_groups', 'event_notes',
                'recurrence_parent_id',
            ]);
            $copyData['event_date']          = $current->format('Y-m-d');
            $copyData['recurrence_parent_id'] = $event->recurrence_parent_id ?? $event->id;

            $copy = Event::create($copyData);

            if ($createZoom) {
                $copy = $this->attachZoomToEvent($zoom, $copy);
            }

            $created[] = $copy->load('organizer');
            $current->addWeeks($intervalWeeks);
        }

        if (empty($created)) {
            return response()->json([
                'message' => 'No additional occurrences fit within the current month',
                'events'  => [],
            ]);
        }

        return response()->json([
            'message' => count($created) . ' recurring event(s) created',
            'events'  => $created,
        ], 201);
    }

    /**
     * Internal helper: pick a free meeting account and create a Zoom meeting on an event.
     * Returns the updated event instance.
     */
    private function attachZoomToEvent(ZoomService $zoom, Event $event): Event
    {
        $eventStart = strtotime($event->event_date->format('Y-m-d') . ' ' . $event->event_time);
        $eventEnd   = $eventStart + ($event->event_duration * 60);

        $busyAccountIds = Event::whereDate('event_date', $event->event_date)
            ->whereNotNull('meeting_account_id')
            ->where('id', '!=', $event->id)
            ->get()
            ->filter(function ($other) use ($eventStart, $eventEnd) {
                $otherStart = strtotime($other->event_date->format('Y-m-d') . ' ' . $other->event_time);
                $otherEnd   = $otherStart + ($other->event_duration * 60);
                return $otherStart < $eventEnd && $otherEnd > $eventStart;
            })
            ->pluck('meeting_account_id')
            ->unique()
            ->toArray();

        $account = MeetingAccount::where('is_active', true)
            ->whereNotIn('id', $busyAccountIds)
            ->first();

        if (!$account) {
            return $event;
        }

        try {
            $urls = $zoom->createMeeting($account, $event);
            $event->update([
                'meeting_account_id' => $account->id,
                'event_meet_link'    => $urls['join_url'],
                'event_start_link'   => $urls['start_url'],
            ]);
        } catch (\Throwable $e) {
            // Skip Zoom failure silently for bulk recurrence; link stays null
        }

        return $event->fresh();
    }
}
