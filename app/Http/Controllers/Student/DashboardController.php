<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Invoice;
use App\Models\StudentStreak;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Services\AchievementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private AchievementService $achievements) {}

    public function index()
    {
        $studentId = Auth::id();
        $now       = Carbon::now();

        // ── Groups ─────────────────────────────────────────────────────────────
        $groups = Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with('teacher:id,username')
            ->get()
            ->map(fn($g) => [
                'group_id'   => $g->group_id,
                'group_name' => $g->group_name,
                'teacher'    => $g->teacher?->username,
            ]);

        $groupIds = $groups->pluck('group_id')->toArray();

        // ── Upcoming events (next 7 days) ──────────────────────────────────────
        $upcomingEvents = Event::whereJsonContains('guests', $studentId)
            ->where('event_date', '>=', $now->toDateString())
            ->where('event_date', '<=', $now->copy()->addDays(7)->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get(['id', 'title', 'event_date', 'event_time', 'event_duration'])
            ->map(fn($e) => [
                'id'             => $e->id,
                'title'          => $e->title,
                'event_date'     => $e->event_date?->toDateString(),
                'event_time'     => $e->event_time,
                'event_duration' => $e->event_duration,
            ]);

        // ── Pending homework ───────────────────────────────────────────────────
        $allHomework = Homework::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', (int) $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', (int) $gid);
            }
        })->get(['id', 'homework_title', 'due_date']);

        $submittedHwIds = HomeworkSubmission::where('student_id', $studentId)
            ->where('status', 'submitted')
            ->pluck('homework_id')
            ->toArray();

        $pendingHomework = $allHomework
            ->filter(fn($hw) => !in_array($hw->id, $submittedHwIds))
            ->map(fn($hw) => [
                'id'             => $hw->id,
                'homework_title' => $hw->homework_title,
                'due_date'       => $hw->due_date?->toDateString(),
                'overdue'        => $hw->due_date && $hw->due_date->isPast(),
            ])
            ->values();

        // ── Pending tests ──────────────────────────────────────────────────────
        $allTests = Test::where(function ($q) use ($studentId, $groupIds) {
            $q->whereJsonContains('people_assigned', (int) $studentId);
            foreach ($groupIds as $gid) {
                $q->orWhereJsonContains('groups_assigned', (int) $gid);
            }
        })->get(['id', 'test_title', 'due_date']);

        $submittedTestIds = TestSubmission::where('student_id', $studentId)
            ->where('status', 'submitted')
            ->pluck('test_id')
            ->toArray();

        $pendingTests = $allTests
            ->filter(fn($t) => !in_array($t->id, $submittedTestIds))
            ->map(fn($t) => [
                'id'         => $t->id,
                'test_title' => $t->test_title,
                'due_date'   => $t->due_date?->toDateString(),
                'overdue'    => $t->due_date && $t->due_date->isPast(),
            ])
            ->values();

        // ── Unpaid invoices ────────────────────────────────────────────────────
        $unpaidInvoices = Invoice::where('student_id', $studentId)
            ->whereIn('status', ['issued', 'overdue'])
            ->orderBy('due_date')
            ->get(['id', 'title', 'series', 'number', 'value', 'currency', 'due_date', 'status'])
            ->map(fn($inv) => [
                'id'       => $inv->id,
                'title'    => $inv->title,
                'number'   => $inv->series . '-' . $inv->number,
                'value'    => $inv->value,
                'currency' => $inv->currency,
                'due_date' => $inv->due_date?->toDateString(),
                'status'   => $inv->status,
            ]);

        // ── Streak ─────────────────────────────────────────────────────────────
        $streak = StudentStreak::where('student_id', $studentId)->first();

        // Auto-reset streak if > 7 days since last submission
        if ($streak && $streak->last_submission_at && $streak->last_submission_at->diffInDays($now) > 7) {
            $streak->update(['current_streak' => 0]);
        }

        $streakData = [
            'current_streak'     => $streak?->current_streak ?? 0,
            'longest_streak'     => $streak?->longest_streak ?? 0,
            'last_submission_at' => $streak?->last_submission_at?->toDateString(),
        ];

        // ── Achievements ───────────────────────────────────────────────────────
        $achievementsData = $this->achievements->getAllForStudent($studentId);
        $unlockedCount    = collect($achievementsData)->where('unlocked', true)->count();

        return response()->json([
            'message' => 'Dashboard retrieved successfully',
            'dashboard' => [
                'groups'            => $groups,
                'upcoming_events'   => $upcomingEvents,
                'pending_homework'  => $pendingHomework,
                'pending_tests'     => $pendingTests,
                'unpaid_invoices'   => $unpaidInvoices,
                'streak'            => $streakData,
                'achievements'      => [
                    'unlocked_count' => $unlockedCount,
                    'total'          => count($achievementsData),
                    'list'           => $achievementsData,
                ],
            ],
        ]);
    }

    /**
     * Get only the achievements and streak for the student.
     */
    public function achievements()
    {
        $studentId = Auth::id();
        $now       = Carbon::now();

        $streak = StudentStreak::where('student_id', $studentId)->first();

        if ($streak && $streak->last_submission_at && $streak->last_submission_at->diffInDays($now) > 7) {
            $streak->update(['current_streak' => 0]);
        }

        $achievementsData = $this->achievements->getAllForStudent($studentId);
        $unlockedCount    = collect($achievementsData)->where('unlocked', true)->count();

        return response()->json([
            'message' => 'Achievements retrieved successfully',
            'streak'  => [
                'current_streak'     => $streak?->current_streak ?? 0,
                'longest_streak'     => $streak?->longest_streak ?? 0,
                'last_submission_at' => $streak?->last_submission_at?->toDateString(),
            ],
            'achievements' => [
                'unlocked_count' => $unlockedCount,
                'total'          => count($achievementsData),
                'list'           => $achievementsData,
            ],
        ]);
    }
}
