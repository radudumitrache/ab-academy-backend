<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Group;
use App\Models\MeetingAccount;
use App\Models\User;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
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
            ->map(function ($event) use ($teacherId, $assistantGroupIds) {
                $isManager = (int) $event->event_organizer === $teacherId
                    || collect($event->guest_groups ?? [])->map(fn($g) => (int) $g)->intersect($assistantGroupIds)->isNotEmpty();

                return $isManager ? $event : $event->makeHidden('event_start_link');
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

        if (!$this->canManageEvent($event, Auth::id())) {
            $event->makeHidden('event_start_link');
        }

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
            'guest_groups'    => 'nullable|array',
            'guest_groups.*'  => 'integer|exists:groups,group_id',
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

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully',
            'event'   => $event->load('organizer'),
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

        // Find account IDs already booked for a time-overlapping event on the same date
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

        return response()->json([
            'message'      => 'Zoom meeting created successfully',
            'event'        => $event->fresh('organizer'),
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
