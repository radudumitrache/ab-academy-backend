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
use App\Services\EventAuditLogger;
use App\Services\ZoomService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'type' => ['required', Rule::in(['class', 'meeting', 'other'])],
                'event_date' => 'required|date',
                'event_time' => 'required|date_format:H:i',
                'event_duration' => 'required|integer|min:1|max:' . config('services.zoom.max_meeting_minutes', 240),
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            EventAuditLogger::eventAttempt('admin', $request, null, 422, ['message' => $e->getMessage(), 'errors' => $e->errors()], $e);

            throw $e;
        }

        // Convert event_date + event_time from actor's timezone to UTC before storing
        if (isset($validated['event_date']) && isset($validated['event_time'])) {
            $utc = TimezoneHelper::toUtc($validated['event_date'], $validated['event_time'], Auth::user()->effective_timezone);
            $validated['event_date'] = $utc->format('Y-m-d');
            $validated['event_time'] = $utc->format('H:i');
        }

        try {
            $event = Event::create($validated);
        } catch (\Throwable $e) {
            EventAuditLogger::eventAttempt('admin', $request, null, 500, ['message' => $e->getMessage()], $e);

            throw $e;
        }

        $response = response()->json([
            'message' => 'Event created successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
        ], 201);

        EventAuditLogger::eventAttempt('admin', $request, $event, 201, $response->getData(true));

        return $response;
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

    public function update(Request $request, ZoomService $zoom, $id)
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
            'event_duration' => 'sometimes|integer|min:1|max:' . config('services.zoom.max_meeting_minutes', 240),
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

        $response = [
            'message' => 'Event updated successfully',
            'event'   => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
        ];

        $warning = $this->syncZoomMeeting($zoom, $event, $validated);

        if ($warning) {
            $response['zoom_warning'] = $warning;
        }

        return response()->json($response);
    }

    /**
     * Push a rescheduled or retitled event onto its existing Zoom meeting.
     *
     * Editing an event used to leave its Zoom meeting untouched, so the link pointed at
     * the old time, the old slot stayed blocked for the account and the new slot was
     * never reserved.
     *
     * Returns a warning string when the sync failed — the edit is already saved, so a
     * Zoom outage must not fail the request.
     */
    private function syncZoomMeeting(ZoomService $zoom, Event $event, array $changed): ?string
    {
        $relevant = array_intersect_key($changed, array_flip(['title', 'event_date', 'event_time', 'event_duration']));

        if (!$relevant) {
            return null;
        }

        $event->loadMissing('meetingAccount');

        if (!$event->zoom_meeting_id || !$event->meetingAccount) {
            return null;
        }

        try {
            $zoom->updateMeeting($event->meetingAccount, $event->zoom_meeting_id, $event);
            return null;
        } catch (\Throwable $e) {
            Log::warning('Could not sync Zoom meeting after editing event ' . $event->id, [
                'zoom_meeting_id' => $event->zoom_meeting_id,
                'error'           => $e->getMessage(),
            ]);

            return 'The event was saved, but its Zoom meeting could not be updated and is still scheduled at the previous time: ' . $e->getMessage();
        }
    }

    public function destroy(ZoomService $zoom, $id)
    {
        $event = Event::with('meetingAccount')->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Event not found',
            ], 404);
        }

        $eventTitle = $event->title;
        $warning    = $this->releaseZoomMeeting($zoom, $event);

        $event->delete();

        DatabaseLog::logAction('delete', Event::class, $id, "Event '{$eventTitle}' deleted");

        $response = ['message' => 'Event deleted successfully'];

        if ($warning) {
            $response['zoom_warning'] = $warning;
        }

        return response()->json($response);
    }

    /**
     * Cancel the event's Zoom meeting so its slot goes back into the pool.
     *
     * Deleting an event used to leave the Zoom meeting scheduled, and because account
     * availability is read from the Zoom API those orphans blocked their slot forever.
     *
     * Returns a warning string on failure — the event is still deleted rather than
     * leaving the user stuck behind a Zoom outage.
     */
    private function releaseZoomMeeting(ZoomService $zoom, Event $event): ?string
    {
        if (!$event->zoom_meeting_id || !$event->meetingAccount) {
            return null;
        }

        try {
            $zoom->deleteMeeting($event->meetingAccount, $event->zoom_meeting_id);
            return null;
        } catch (\Throwable $e) {
            Log::warning('Could not delete Zoom meeting for event ' . $event->id, [
                'zoom_meeting_id' => $event->zoom_meeting_id,
                'error'           => $e->getMessage(),
            ]);

            return 'The event was deleted, but its Zoom meeting could not be cancelled and may still block that time slot: ' . $e->getMessage();
        }
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

        $before  = EventAuditLogger::meetingSnapshot($event);
        $outcome = null;

        try {
            $outcome = $zoom->bookMeetingFor($event);

            if (!$outcome['account']) {
                $response = $this->noAccountResponse($zoom, $event, $outcome);
            } else {
                $event->update([
                    'meeting_account_id' => $outcome['account']->id,
                    'zoom_meeting_id'    => $outcome['meeting']['meeting_id'],
                    'event_meet_link'    => $outcome['meeting']['join_url'],
                    'event_start_link'   => $outcome['meeting']['start_url'],
                ]);

                $event->refresh();

                $response = response()->json([
                    'message'      => !empty($outcome['reused']) ? 'This event already has a Zoom meeting' : 'Zoom meeting created successfully',
                    'event'        => $this->formatEvent($event->load('organizer'), Auth::user()->effective_timezone),
                    'meeting_link' => $outcome['meeting']['join_url'],
                    'start_link'   => $outcome['meeting']['start_url'],
                ]);
            }
        } catch (\Throwable $e) {
            EventAuditLogger::zoomAttempt('admin', $event, $outcome, $before, 500, ['message' => $e->getMessage()], $e);

            throw $e;
        }

        EventAuditLogger::zoomAttempt('admin', $event, $outcome, $before, $response->getStatusCode(), $response->getData(true));

        return $response;
    }

    /**
     * Build the failure response when no account could be booked.
     *
     * A pool that is genuinely full (422) and a pool we could not reach (502) are
     * different answers: the first means "pick another slot", the second means "retry".
     * These used to collapse into one 422 that read as "all accounts are busy", which
     * is why a rate-limited Zoom API looked like a fully booked one.
     */
    private function noAccountResponse(ZoomService $zoom, Event $event, array $outcome)
    {
        if ($outcome['total'] === 0) {
            return response()->json(['message' => 'No active meeting accounts configured'], 422);
        }

        $eventStart  = $zoom->eventStartUtc($event);
        $unreachable = !empty($outcome['errors']);

        if ($unreachable) {
            $message = $outcome['busy']
                ? 'Some meeting accounts are busy and the rest could not be reached — please retry'
                : 'No meeting account could be reached (Zoom API error) — please retry';
        } else {
            $message = 'All meeting accounts are busy during this time slot';
        }

        return response()->json([
            'message'        => $message,
            'busy_accounts'  => $outcome['busy'],
            'error_accounts' => $outcome['errors'],
            'checked_window' => [
                'start_utc' => $eventStart->toIso8601String(),
                'end_utc'   => $eventStart->copy()->addMinutes((int) $event->event_duration)->toIso8601String(),
            ],
        ], $unreachable ? 502 : 422);
    }

    /**
     * Return a freshly issued host start link for the event's Zoom meeting.
     *
     * The start_url captured at creation embeds a token that expires about two hours
     * later, so for anything booked further ahead the stored link is already dead by
     * class time.
     */
    public function zoomStartLink(ZoomService $zoom, $id)
    {
        $event = Event::with('meetingAccount')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        if (!$event->zoom_meeting_id || !$event->meetingAccount) {
            $response = response()->json(['message' => 'This event has no Zoom meeting to start'], 422);
            EventAuditLogger::startLinkAttempt('admin', $event, null, null, null, 422, $response->getData(true));

            return $response;
        }

        try {
            $meeting = $zoom->getMeeting($event->meetingAccount, $event->zoom_meeting_id);
        } catch (\Throwable $e) {
            Log::warning('Could not refresh Zoom start link for event ' . $event->id, [
                'zoom_meeting_id' => $event->zoom_meeting_id,
                'error'           => $e->getMessage(),
            ]);

            $response = response()->json([
                'message' => 'Could not retrieve a start link from Zoom: ' . $e->getMessage(),
            ], 502);

            EventAuditLogger::startLinkAttempt('admin', $event, null, null, null, 502, $response->getData(true), $e);

            return $response;
        }

        // Keep the stored copy in step so listings show the newest link we have seen.
        if (!empty($meeting['start_url'])) {
            $event->update(['event_start_link' => $meeting['start_url']]);
        }

        $payload = [
            'message'      => 'Start link retrieved successfully',
            'start_link'   => $meeting['start_url'] ?? null,
            'meeting_link' => $meeting['join_url'] ?? $event->event_meet_link,
        ];

        // Zoom refuses to start a meeting while its host account is in another one, and
        // the teacher only finds out after clicking the link. Say so up front.
        $liveMeetings   = null;
        $blocking       = null;
        $liveCheckError = null;

        try {
            $liveMeetings = $zoom->currentLiveMeetings($event->meetingAccount);
            $blocking     = $zoom->otherLiveMeeting($liveMeetings, (string) $event->zoom_meeting_id);

            if ($blocking !== null) {
                $payload['start_warning'] = 'The Zoom account for this meeting is currently in another meeting'
                    . (!empty($blocking['topic']) ? ' ("' . $blocking['topic'] . '")' : '')
                    . '. Zoom will not let this one start until that meeting is ended.';
                $payload['blocking_meeting'] = [
                    'id'    => $blocking['id'] ?? null,
                    'topic' => $blocking['topic'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // The link itself is valid; failing to look for a conflict must not withhold it.
            $liveCheckError = $e;

            Log::warning('Could not check for a live meeting before handing out a start link for event ' . $event->id, [
                'error' => $e->getMessage(),
            ]);
        }

        $response = response()->json($payload);

        EventAuditLogger::startLinkAttempt('admin', $event, $meeting, $liveMeetings, $blocking, 200, $response->getData(true), null, $liveCheckError);

        return $response;
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
        $outcome = $zoom->bookMeetingFor($event);

        if ($outcome['account']) {
            $event->update([
                'meeting_account_id' => $outcome['account']->id,
                'zoom_meeting_id'    => $outcome['meeting']['meeting_id'],
                'event_meet_link'    => $outcome['meeting']['join_url'],
                'event_start_link'   => $outcome['meeting']['start_url'],
            ]);
        } else {
            Log::warning('No meeting account available during recurrence for event ' . $event->id, [
                'busy_accounts'  => $outcome['busy'],
                'error_accounts' => $outcome['errors'],
            ]);
        }

        return $event->fresh();
    }
}
