<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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
    /**
     * Format an event for API output, converting UTC date/time to the given timezone.
     * Pass $showStartLink = false to omit event_start_link for non-managers.
     */
    private function formatEvent(Event $event, string $timezone, bool $showStartLink = true): array
    {
        $utcCarbon = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );
        $local = TimezoneHelper::fromUtc($utcCarbon, $timezone);

        $data = [
            'id'                   => $event->id,
            'title'                => $event->title,
            'type'                 => $event->type,
            'event_date'           => $local['date'],
            'event_time'           => $local['time'],
            'event_duration'       => $event->event_duration,
            'event_organizer'      => $event->event_organizer,
            'guests'               => $event->guests,
            'guest_groups'         => $event->guest_groups,
            'event_meet_link'      => $event->event_meet_link,
            'event_notes'          => $event->event_notes,
            'meeting_account_id'   => $event->meeting_account_id,
            'recurrence_parent_id' => $event->recurrence_parent_id,
            'organizer'            => $event->organizer ? [
                'id'       => $event->organizer->id,
                'username' => $event->organizer->username,
            ] : null,
            'created_at'           => $event->created_at,
            'updated_at'           => $event->updated_at,
        ];

        if ($showStartLink) {
            $data['event_start_link'] = $event->event_start_link;
        }

        return $data;
    }

    /**
     * Returns true if the given teacher can manage (edit/delete/zoom) an event.
     * This is the case when they are the organizer, or when they are an assistant
     * teacher of at least one group that is in the event's guest_groups.
     */
    private function canManageEvent(Event $event, int $teacherId): bool
    {
        if ((int) $event->event_organizer === $teacherId) {
            return true;
        }

        $groupIds = collect($event->guest_groups ?? [])->map(fn($g) => (int) $g)->filter()->toArray();

        if (empty($groupIds)) {
            return false;
        }

        return Group::whereIn('group_id', $groupIds)
            ->whereHas('assistantTeachers', fn($q) => $q->where('teacher_id', $teacherId))
            ->exists();
    }

    /**
     * List all events where the teacher is the organizer or is in the guest list.
     */
    public function index()
    {
        $teacherId = Auth::id();

        $assistantGroupIds = Group::whereHas('assistantTeachers', fn($q) => $q->where('teacher_id', $teacherId))
            ->pluck('group_id')
            ->toArray();

        $userTimezone = Auth::user()->effective_timezone;

        $events = Event::with('organizer')
            ->where(function ($query) use ($teacherId, $assistantGroupIds) {
                $query->where('event_organizer', $teacherId)
                      ->orWhereJsonContains('guests', $teacherId);

                foreach ($assistantGroupIds as $groupId) {
                    $query->orWhereJsonContains('guest_groups', $groupId);
                }
            })
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->map(function ($event) use ($teacherId, $assistantGroupIds, $userTimezone) {
                $isManager = (int) $event->event_organizer === $teacherId
                    || collect($event->guest_groups ?? [])->map(fn($g) => (int) $g)->intersect($assistantGroupIds)->isNotEmpty();

                return $this->formatEvent($event, $userTimezone, $isManager);
            });

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

        $isManager = $this->canManageEvent($event, Auth::id());

        return response()->json([
            'message'     => 'Event retrieved successfully',
            'event'       => $this->formatEvent($event, Auth::user()->effective_timezone, $isManager),
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
            'guest_groups'    => 'nullable|array',
            'guest_groups.*'  => 'integer|exists:groups,group_id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes'     => 'nullable|string',
        ]);

        $validated['event_organizer'] = Auth::id();

        // Convert event_date + event_time from actor's timezone to UTC before storing
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $utc = TimezoneHelper::toUtc($validated['event_date'], $validated['event_time'], Auth::user()->effective_timezone);
            $validated['event_date'] = $utc->format('Y-m-d');
            $validated['event_time'] = $utc->format('H:i');
        }

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone, true),
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

        if (!$this->canManageEvent($event, Auth::id())) {
            return response()->json(['message' => 'Unauthorized — only the event organizer or an assistant teacher of the event\'s groups can edit this event'], 403);
        }

        $validated = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'type'            => ['sometimes', Rule::in(['class', 'meeting', 'other'])],
            'event_date'      => 'sometimes|date',
            'event_time'      => 'sometimes|date_format:H:i',
            'event_duration'  => 'sometimes|integer|min:1',
            'guests'          => 'nullable|array',
            'guests.*'        => 'integer|exists:users,id',
            'guest_groups'    => 'nullable|array',
            'guest_groups.*'  => 'integer|exists:groups,group_id',
            'event_meet_link' => 'nullable|url|max:2048',
            'event_notes'     => 'nullable|string',
        ]);

        // Convert event_date + event_time from actor's timezone to UTC before storing
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $utc = TimezoneHelper::toUtc($validated['event_date'], $validated['event_time'], Auth::user()->effective_timezone);
            $validated['event_date'] = $utc->format('Y-m-d');
            $validated['event_time'] = $utc->format('H:i');
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone, true),
        ]);
    }

    /**
     * Record attendance for each guest at the event.
     * Only the organizer may mark attendance.
     * Accepts an array of { student_id, status } entries — student must be on the guest list.
     */
    public function markAttendance(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if (!$this->canManageEvent($event, Auth::id())) {
            return response()->json(['message' => 'Unauthorized — only the event organizer or an assistant teacher of the event\'s groups can mark attendance'], 403);
        }

        $validated = $request->validate([
            'attendance'            => 'required|array',
            'attendance.*.student_id' => 'required|integer|exists:users,id',
            'attendance.*.status'     => ['required', Rule::in(['present', 'absent', 'motivated_absent'])],
        ]);

        // Collect direct guest IDs
        $guestIds = collect($event->guests ?? [])->map(function ($guest) {
            return is_array($guest) ? ($guest['id'] ?? null) : $guest;
        })->filter()->map(fn ($g) => (int) $g);

        // Also include all students from invited guest_groups
        $groupIds = collect($event->guest_groups ?? [])->map(fn ($g) => (int) $g)->filter()->toArray();
        if (!empty($groupIds)) {
            $groupMemberIds = Group::whereIn('group_id', $groupIds)
                ->with('students:id')
                ->get()
                ->flatMap(fn ($group) => $group->students->pluck('id'))
                ->map(fn ($id) => (int) $id);
            $guestIds = $guestIds->merge($groupMemberIds);
        }

        $guestIds = $guestIds->unique()->toArray();

        $notOnGuestList = collect($validated['attendance'])
            ->pluck('student_id')
            ->filter(fn ($sid) => !in_array((int) $sid, $guestIds))
            ->values();

        if ($notOnGuestList->isNotEmpty()) {
            return response()->json([
                'message'           => 'Some users are not on the guest list for this event',
                'not_on_guest_list' => $notOnGuestList,
            ], 422);
        }

        foreach ($validated['attendance'] as $entry) {
            Attendance::updateOrCreate(
                ['event_id' => $event->id, 'student_id' => $entry['student_id']],
                ['status'   => $entry['status']]
            );
        }

        $records = Attendance::where('event_id', $event->id)
            ->with('student:id,username,email,role')
            ->get()
            ->map(fn ($a) => [
                'student_id' => $a->student_id,
                'username'   => $a->student?->username,
                'status'     => $a->status,
            ]);

        return response()->json([
            'message'    => 'Attendance recorded successfully',
            'attendance' => $records,
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

        if (!$this->canManageEvent($event, Auth::id())) {
            return response()->json(['message' => 'Unauthorized — only the event organizer or an assistant teacher of the event\'s groups can add guests'], 403);
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
     * Automatically select an available meeting account and create a Zoom meeting for the event.
     * Only the organizer may call this.
     */
    public function createZoomMeeting(ZoomService $zoom, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if (!$this->canManageEvent($event, Auth::id())) {
            return response()->json(['message' => 'Unauthorized — only the event organizer or an assistant teacher of the event\'s groups can create a Zoom meeting'], 403);
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

        return response()->json([
            'message'      => 'Zoom meeting created successfully',
            'event'        => $this->formatEvent($event->fresh('organizer'), Auth::user()->effective_timezone, true),
            'meeting_link' => $urls['join_url'],
            'start_link'   => $urls['start_url'],
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

        if (!$this->canManageEvent($event, Auth::id())) {
            return response()->json(['message' => 'Unauthorized — only the event organizer or an assistant teacher of the event\'s groups can delete this event'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    /**
     * Return attendance records for an event.
     * Any teacher who is the organizer or on the guest list may view.
     */
    public function getAttendance(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $teacherId = Auth::id();
        $isOrganizer = (int) $event->event_organizer === $teacherId;
        $isGuest     = in_array($teacherId, $event->guests ?? []);

        if (!$isOrganizer && !$isGuest) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Resolve all expected guests (direct + from guest_groups)
        $directGuestIds = collect($event->guests ?? [])->map(fn($g) => (int) $g)->unique();

        $groupIds = collect($event->guest_groups ?? [])->map(fn($g) => (int) $g)->filter()->toArray();
        $groupMemberIds = collect();
        if (!empty($groupIds)) {
            $groupMemberIds = \App\Models\Group::whereIn('group_id', $groupIds)
                ->with('students:id')
                ->get()
                ->flatMap(fn($group) => $group->students->pluck('id'))
                ->map(fn($id) => (int) $id);
        }

        $allGuestIds = $directGuestIds->merge($groupMemberIds)->unique()->toArray();

        // Recorded statuses keyed by student_id
        $recorded = Attendance::where('event_id', $event->id)
            ->whereIn('student_id', $allGuestIds)
            ->pluck('status', 'student_id');

        $users = \App\Models\User::whereIn('id', $allGuestIds)->get(['id', 'username', 'email', 'role']);

        $attendance = $users->map(fn($u) => [
            'student_id' => $u->id,
            'username'   => $u->username,
            'email'      => $u->email,
            'role'       => $u->role,
            'status'     => $recorded->get($u->id), // null = not yet marked
        ]);

        return response()->json([
            'message'    => 'Attendance retrieved successfully',
            'event_id'   => $event->id,
            'attendance' => $attendance->values(),
        ]);
    }
}
