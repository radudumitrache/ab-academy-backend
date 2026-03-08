<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\StudentAchievement;
use App\Models\StudentStreak;
use App\Models\TestSubmission;
use Carbon\Carbon;

class AchievementService
{
    /**
     * All achievement definitions.
     * Each entry: key => ['name', 'description']
     */
    public static array $definitions = [
        'early_bird'      => ['name' => 'Early Bird',      'description' => 'Submit homework 2+ days before the deadline'],
        'on_fire'         => ['name' => 'On Fire',         'description' => '3-day submission streak'],
        'perfect_week'    => ['name' => 'Perfect Week',    'description' => 'Submit all assigned homework in a week'],
        'first_of_class'  => ['name' => 'First of Class',  'description' => 'Be the first student to submit an assignment'],
        'bookworm'        => ['name' => 'Bookworm',        'description' => 'Complete 10 assignments (homework + tests)'],
        'diamond_student' => ['name' => 'Diamond Student', 'description' => 'Maintain a 30-day streak'],
        'rocket_launch'   => ['name' => 'Rocket Launch',   'description' => 'Submit 3 assignments in one day'],
    ];

    /**
     * Called after a homework or test is submitted.
     *
     * @param  int         $studentId
     * @param  Carbon      $submittedAt
     * @param  string      $type        'homework' | 'test'
     * @param  int|null    $homeworkId  only for homework submissions
     * @return array  list of newly unlocked achievement keys
     */
    public function recordSubmission(int $studentId, Carbon $submittedAt, string $type, ?int $homeworkId = null): array
    {
        $streak  = $this->updateStreak($studentId, $submittedAt);
        $unlocked = $this->evaluateAchievements($studentId, $submittedAt, $type, $homeworkId, $streak);

        return $unlocked;
    }

    // ── Streak ─────────────────────────────────────────────────────────────────

    /**
     * Update the student's streak record and return the updated model.
     *
     * Rules:
     *  - If no previous submission OR last submission was more than 7 days ago → reset to 1.
     *  - If last submission was today (same calendar day) → no change (already counted).
     *  - Otherwise → increment by 1.
     */
    public function updateStreak(int $studentId, Carbon $submittedAt): StudentStreak
    {
        $streak = StudentStreak::firstOrCreate(
            ['student_id' => $studentId],
            ['current_streak' => 0, 'longest_streak' => 0, 'last_submission_at' => null]
        );

        $last = $streak->last_submission_at;

        if (!$last) {
            // First ever submission
            $streak->current_streak = 1;
        } elseif ($last->isSameDay($submittedAt)) {
            // Already submitted today — don't double-count
            return $streak;
        } elseif ($last->diffInDays($submittedAt) > 7) {
            // Gap > 7 days — reset
            $streak->current_streak = 1;
        } else {
            $streak->current_streak += 1;
        }

        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->last_submission_at = $submittedAt;
        $streak->save();

        return $streak;
    }

    // ── Achievement evaluation ─────────────────────────────────────────────────

    /**
     * Check all achievements and unlock any newly earned ones.
     * Returns the list of newly unlocked keys.
     */
    private function evaluateAchievements(
        int $studentId,
        Carbon $submittedAt,
        string $type,
        ?int $homeworkId,
        StudentStreak $streak
    ): array {
        $unlocked = [];

        $alreadyUnlocked = StudentAchievement::where('student_id', $studentId)
            ->pluck('achievement_key')
            ->flip(); // key => index for O(1) lookup

        $checks = [
            'early_bird'      => fn() => $this->checkEarlyBird($studentId, $type, $homeworkId, $submittedAt),
            'on_fire'         => fn() => $streak->current_streak >= 3,
            'perfect_week'    => fn() => $this->checkPerfectWeek($studentId, $submittedAt),
            'first_of_class'  => fn() => $this->checkFirstOfClass($studentId, $type, $homeworkId),
            'bookworm'        => fn() => $this->checkBookworm($studentId),
            'diamond_student' => fn() => $streak->current_streak >= 30,
            'rocket_launch'   => fn() => $this->checkRocketLaunch($studentId, $submittedAt),
        ];

        foreach ($checks as $key => $check) {
            if (isset($alreadyUnlocked[$key])) {
                continue; // already earned
            }
            if ($check()) {
                StudentAchievement::create([
                    'student_id'      => $studentId,
                    'achievement_key' => $key,
                    'unlocked_at'     => $submittedAt,
                ]);
                $unlocked[] = $key;
            }
        }

        return $unlocked;
    }

    // ── Individual checks ──────────────────────────────────────────────────────

    /**
     * Early Bird: submitted homework 2+ days before due_date.
     */
    private function checkEarlyBird(int $studentId, string $type, ?int $homeworkId, Carbon $submittedAt): bool
    {
        if ($type !== 'homework' || !$homeworkId) {
            return false;
        }

        $homework = Homework::find($homeworkId);
        if (!$homework || !$homework->due_date) {
            return false;
        }

        return $submittedAt->diffInDays(Carbon::parse($homework->due_date), false) >= 2;
    }

    /**
     * Perfect Week: student submitted ALL homework assigned to them that was due in the
     * current ISO week (Mon–Sun containing $submittedAt), and every one is submitted.
     */
    private function checkPerfectWeek(int $studentId, Carbon $submittedAt): bool
    {
        $weekStart = $submittedAt->copy()->startOfWeek();
        $weekEnd   = $submittedAt->copy()->endOfWeek();

        // Find homework due this week assigned to this student (via group or direct)
        $groupIds = \App\Models\Group::whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->pluck('group_id')
            ->toArray();

        $homeworkThisWeek = Homework::whereBetween('due_date', [$weekStart, $weekEnd])
            ->where(function ($q) use ($studentId, $groupIds) {
                $q->whereJsonContains('people_assigned', (int) $studentId);
                foreach ($groupIds as $gid) {
                    $q->orWhereJsonContains('groups_assigned', (int) $gid);
                }
            })
            ->pluck('id');

        if ($homeworkThisWeek->isEmpty()) {
            return false;
        }

        $submitted = HomeworkSubmission::where('student_id', $studentId)
            ->whereIn('homework_id', $homeworkThisWeek)
            ->where('status', 'submitted')
            ->count();

        return $submitted >= $homeworkThisWeek->count();
    }

    /**
     * First of Class: this student was the first to submit this particular assignment.
     */
    private function checkFirstOfClass(int $studentId, string $type, ?int $assignmentId): bool
    {
        if (!$assignmentId) {
            return false;
        }

        if ($type === 'homework') {
            $earliestStudentId = HomeworkSubmission::where('homework_id', $assignmentId)
                ->where('status', 'submitted')
                ->orderBy('submitted_at')
                ->value('student_id');
        } else {
            $earliestStudentId = TestSubmission::where('test_id', $assignmentId)
                ->where('status', 'submitted')
                ->orderBy('submitted_at')
                ->value('student_id');
        }

        return $earliestStudentId === $studentId;
    }

    /**
     * Bookworm: total submitted homework + tests >= 10.
     */
    private function checkBookworm(int $studentId): bool
    {
        $hwCount   = HomeworkSubmission::where('student_id', $studentId)->where('status', 'submitted')->count();
        $testCount = TestSubmission::where('student_id', $studentId)->where('status', 'submitted')->count();

        return ($hwCount + $testCount) >= 10;
    }

    /**
     * Rocket Launch: 3+ submissions (hw + test combined) on the same calendar day.
     */
    private function checkRocketLaunch(int $studentId, Carbon $submittedAt): bool
    {
        $dayStart = $submittedAt->copy()->startOfDay();
        $dayEnd   = $submittedAt->copy()->endOfDay();

        $hwToday   = HomeworkSubmission::where('student_id', $studentId)
            ->where('status', 'submitted')
            ->whereBetween('submitted_at', [$dayStart, $dayEnd])
            ->count();

        $testToday = TestSubmission::where('student_id', $studentId)
            ->where('status', 'submitted')
            ->whereBetween('submitted_at', [$dayStart, $dayEnd])
            ->count();

        return ($hwToday + $testToday) >= 3;
    }

    // ── Public helper ──────────────────────────────────────────────────────────

    /**
     * Return all achievements for a student: unlocked ones with date, locked ones without.
     */
    public function getAllForStudent(int $studentId): array
    {
        $unlocked = StudentAchievement::where('student_id', $studentId)
            ->get()
            ->keyBy('achievement_key');

        $result = [];
        foreach (self::$definitions as $key => $def) {
            $record = $unlocked->get($key);
            $result[] = [
                'key'         => $key,
                'name'        => $def['name'],
                'description' => $def['description'],
                'unlocked'    => (bool) $record,
                'unlocked_at' => $record ? $record->unlocked_at->toDateString() : null,
            ];
        }

        return $result;
    }
}
