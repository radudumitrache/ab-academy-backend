<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Status constants
     */
    const STATUS_UPCOMING = 'upcoming';
    const STATUS_TO_BE_CORRECTED = 'to_be_corrected';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the status history for the exam.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ExamStatusHistory::class);
    }

    /**
     * Get the students enrolled in this exam.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_exam')
                    ->withTimestamps();
    }

    /**
     * Update the status and record the change in history.
     *
     * @param string $status
     * @param int|null $userId
     * @return bool
     */
    public function updateStatus(string $status, ?int $userId = null): bool
    {
        // Only record history if status is actually changing
        if ($this->status !== $status) {
            // Record the old status in history
            $this->statusHistory()->create([
                'old_status' => $this->status,
                'new_status' => $status,
                'changed_by_user_id' => $userId,
            ]);

            // Update the status
            $this->status = $status;
            return $this->save();
        }

        return false;
    }
}
