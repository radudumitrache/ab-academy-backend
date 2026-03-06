<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'group_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_name',
        'group_teacher',
        'description',
        'class_code',
        'schedule_days',
        'normal_schedule', // Keep for backward compatibility
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'normal_schedule'  => 'datetime',
        'schedule_days'    => 'array',
    ];

    protected $appends = [
        'group_members',
    ];

    /**
     * Get the teacher that owns the group.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'group_teacher', 'id');
    }

    /**
     * The students that belong to the group.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'group_student', 'group_id', 'student_id')
                    ->withTimestamps();
    }

    public function getGroupMembersAttribute()
    {
        if ($this->relationLoaded('students')) {
            return $this->students->pluck('id')->values()->all();
        }

        return $this->students()->pluck('users.id')->values()->all();
    }
    
    /**
     * Get available days for scheduling.
     */
    public static function getAvailableDays(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    }

    /**
     * Get available times for scheduling in 30-minute intervals (08:00–20:30).
     */
    public static function getAvailableTimes(): array
    {
        $times = [];

        for ($hour = 8; $hour <= 20; $hour++) {
            $h = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $times[] = "{$h}:00";
            $times[] = "{$h}:30";
        }

        return $times;
    }

    /**
     * Generate a unique 8-character alphanumeric class code.
     */
    public static function generateClassCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('class_code', $code)->exists());

        return $code;
    }

    /**
     * Get formatted schedule as a human-readable string (all slots joined by ", ").
     * Example: "Monday at 09:00, Wednesday at 11:00"
     */
    public function getFormattedScheduleAttribute(): ?string
    {
        if (empty($this->schedule_days)) {
            return null;
        }

        $parts = array_map(
            fn($s) => "{$s['day']} at {$s['time']}",
            $this->schedule_days
        );

        return implode(', ', $parts);
    }
}
