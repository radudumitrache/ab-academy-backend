<?php

namespace App\Http\Controllers\Student;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolveStudentGroups;
use App\Models\Attendance;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    use ResolveStudentGroups;

    /**
     * List all events the authenticated student has access to:
     * - directly (their ID appears in `guests`), or
     * - via group invite (any of their groups appears in `guest_groups`).
     */
    public function index(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year  = $request->query('year',  now()->year);

        if (!is_numeric($month) || $month < 1 || $month > 12) {
            return response()->json(['message' => 'Invalid month. Must be between 1 and 12.'], 422);
        }
        if (!is_numeric($year) || $year < 2000 || $year > 2100) {
            return response()->json(['message' => 'Invalid year.'], 422);
        }

        $studentId = Auth::id();
        [$groupIds] = $this->studentGroupContext($studentId);

        $events = Event::where(function ($q) use ($studentId, $groupIds) {
                $q->whereJsonContains('guests', $studentId);
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('guest_groups', $gid);
                }
            })
            ->whereYear('event_date', (int) $year)
            ->whereMonth('event_date', (int) $month)
            ->with('organizer:id,username')
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->unique('id');

        // Load attendance records for this student in one query
        $eventIds = $events->pluck('id')->toArray();
        $attendanceMap = Attendance::where('student_id', $studentId)
            ->whereIn('event_id', $eventIds)
            ->pluck('status', 'event_id');

        $events = $events->map(fn($e) => $this->format($e, $attendanceMap->get($e->id)))->values();

        return response()->json([
            'message' => 'Events retrieved successfully',
            'count'   => $events->count(),
            'events'  => $events,
        ]);
    }

    /**
     * Show a single event. Access is granted via direct invite or group invite.
     */
    public function show($id)
    {
        $studentId = Auth::id();
        [$groupIds] = $this->studentGroupContext($studentId);

        $event = Event::with('organizer:id,username')->find($id);

        if (!$event || !$this->studentHasAccess($event, $studentId, $groupIds)) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $attendance = Attendance::where('event_id', $event->id)
            ->where('student_id', $studentId)
            ->value('status');

        return response()->json([
            'message' => 'Event retrieved successfully',
            'event'   => $this->format($event, $attendance),
        ]);
    }

    private function studentHasAccess(Event $event, int $studentId, array $groupIds): bool
    {
        if (in_array($studentId, $event->guests ?? [])) {
            return true;
        }
        foreach ($groupIds as $gid) {
            if (in_array($gid, $event->guest_groups ?? [])) {
                return true;
            }
        }
        return false;
    }

    private function format(Event $event, ?string $attendanceStatus = null): array
    {
        $utcCarbon = Carbon::createFromFormat(
            'Y-m-d H:i',
            $event->event_date->format('Y-m-d') . ' ' . substr($event->event_time, 0, 5),
            'UTC'
        );
        $local = TimezoneHelper::fromUtc($utcCarbon, Auth::user()->effective_timezone);

        return [
            'id'                 => $event->id,
            'title'              => $event->title,
            'type'               => $event->type,
            'event_date'         => $local['date'],
            'event_time'         => $local['time'],
            'event_duration'     => $event->event_duration,
            'event_meet_link'    => $event->event_meet_link,
            'event_notes'        => $event->event_notes,
            'attendance_status'  => $attendanceStatus, // null if not yet marked
            'organizer'          => $event->organizer ? [
                'id'       => $event->organizer->id,
                'username' => $event->organizer->username,
            ] : null,
        ];
    }
}
