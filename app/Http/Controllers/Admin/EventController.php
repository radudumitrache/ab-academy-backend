<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DatabaseLog;
use App\Models\Event;
use App\Models\Group;
use App\Helpers\TimezoneHelper;
use App\Models\MeetingAccount;
use App\Models\User;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with('organizer')->orderBy('event_date')->orderBy('event_time');

        if ($request->filled('organizer_id')) {
            $query->where('event_organizer', $request->integer('organizer_id'));
        }

        $userTimezone = Auth::user()->effective_timezone;

        return response()->json([
            'message' => 'Events retrieved successfully',
            'events'  => $query->get()->map(fn($e) => $this->formatEvent($e, $userTimezone)),
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
            'guest_groups.*' => 'integer|exists:groups,group_id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        // Convert event_date + event_time from actor's timezone to UTC before storing
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $utc = TimezoneHelper::toUtc($validated['event_date'], $validated['event_time'], Auth::user()->effective_timezone);
            $validated['event_date'] = $utc->format('Y-m-d');
            $validated['event_time'] = $utc->format('H:i');
        }

        $event = Event::create($validated);

        DatabaseLog::logAction('create', Event::class, $event->id, "Event '{$event->title}' created");

        return response()->json([
            'message' => 'Event created successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
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
            'event'   => $this->formatEvent($event, Auth::user()->effective_timezone),
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
            'guest_groups.*' => 'integer|exists:groups,group_id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes' => 'nullable|string',
        ]);

        // Convert event_date + event_time from actor's timezone to UTC before storing
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $utc = TimezoneHelper::toUtc($validated['event_date'], $validated['event_time'], Auth::user()->effective_timezone);
            $validated['event_date'] = $utc->format('Y-m-d');
            $validated['event_time'] = $utc->format('H:i');
        }

        $event->update($validated);

        DatabaseLog::logAction('update', Event::class, $event->id, "Event '{$event->title}' updated");

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
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

        $eventTitle = $event->title;
        $event->delete();

        DatabaseLog::logAction('delete', Event::class, $id, "Event '{$eventTitle}' deleted");

        return response()->json([
            'message' => 'Event deleted successfully',
        ]);
    }

    /**
     * Return all attendance records for an event, including all expected guests
     * (direct + from guest_groups), with their recorded status (null if not yet marked).
     */
    public function getAttendance($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // Resolve direct guest IDs
        $directGuestIds = collect($event->guests ?? [])->map(fn($g) => (int) $g)->unique();

        // Resolve group member IDs
        $groupIds = collect($event->guest_groups ?? [])->map(fn($g) => (int) $g)->filter()->toArray();
        $groupMemberIds = collect();
        if (!empty($groupIds)) {
            $groupMemberIds = Group::whereIn('group_id', $groupIds)
                ->with('students:id')
                ->get()
                ->flatMap(fn($group) => $group->students->pluck('id'))
                ->map(fn($id) => (int) $id);
        }

        $allGuestIds = $directGuestIds->merge($groupMemberIds)->unique()->toArray();

        // Fetch recorded attendance keyed by student_id
        $recorded = Attendance::where('event_id', $event->id)
            ->whereIn('student_id', $allGuestIds)
            ->pluck('status', 'student_id');

        // Fetch user details for all guests
        $users = User::whereIn('id', $allGuestIds)->get(['id', 'username', 'email', 'role']);

        $attendance = $users->map(fn($user) => [
            'student_id' => $user->id,
            'username'   => $user->username,
            'email'      => $user->email,
            'role'       => $user->role,
            'status'     => $recorded->get($user->id), // null if not yet marked
        ]);

        return response()->json([
            'message'    => 'Attendance retrieved successfully',
            'event_id'   => $event->id,
            'attendance' => $attendance->values(),
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

        $eventStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );

        $accounts = MeetingAccount::where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            return response()->json(['message' => 'No active meeting accounts configured'], 422);
        }

        $account = null;
        foreach ($accounts as $candidate) {
            try {
                if (!$zoom->hasOverlappingMeeting($candidate, $eventStart, $event->event_duration)) {
                    $account = $candidate;
                    break;
                }
            } catch (\Throwable $e) {
                // Account unreachable — skip and try next
                continue;
            }
        }

        if (!$account) {
            return response()->json(['message' => 'All meeting accounts are busy during this time slot'], 422);
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

        DatabaseLog::logAction('update', Event::class, $event->id, "Zoom meeting created for event '{$event->title}'");

        return response()->json([
            'message'      => 'Zoom meeting created successfully',
            'event'        => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
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

        DatabaseLog::logAction('create', Event::class, $event->id, count($created) . " recurring event(s) created from event '{$event->title}'");

        return response()->json([
            'message' => count($created) . ' recurring event(s) created',
            'events'  => $created,
        ], 201);
    }

    /**
     * Format an event for API output, converting UTC date/time to the given timezone.
     */
    private function formatEvent(Event $event, string $timezone): array
    {
        $utcCarbon = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );
        $local = TimezoneHelper::fromUtc($utcCarbon, $timezone);

        return [
            'id'                 => $event->id,
            'title'              => $event->title,
            'type'               => $event->type,
            'event_date'         => $local['date'],
            'event_time'         => $local['time'],
            'event_duration'     => $event->event_duration,
            'event_organizer'    => $event->event_organizer,
            'guests'             => $event->guests,
            'guest_groups'       => $event->guest_groups,
            'event_meet_link'    => $event->event_meet_link,
            'event_start_link'   => $event->event_start_link,
            'event_notes'        => $event->event_notes,
            'meeting_account_id' => $event->meeting_account_id,
            'recurrence_parent_id' => $event->recurrence_parent_id,
            'organizer'          => $event->organizer ? [
                'id'       => $event->organizer->id,
                'username' => $event->organizer->username,
            ] : null,
            'created_at'         => $event->created_at,
            'updated_at'         => $event->updated_at,
        ];
    }

    /**
     * Internal helper: pick a free meeting account and create a Zoom meeting on an event.
     * Returns the updated event instance.
     */
    private function attachZoomToEvent(ZoomService $zoom, Event $event): Event
    {
        $eventStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );

        $account = null;
        foreach (MeetingAccount::where('is_active', true)->get() as $candidate) {
            try {
                if (!$zoom->hasOverlappingMeeting($candidate, $eventStart, $event->event_duration)) {
                    $account = $candidate;
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

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
