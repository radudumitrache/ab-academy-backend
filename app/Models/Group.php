<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'schedule_day',
        'schedule_time',
        'normal_schedule', // Keep for backward compatibility
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'normal_schedule' => 'datetime',
        'schedule_time' => 'datetime',
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
     *
     * @return array
     */
    public static function getAvailableDays()
    {
        return [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];
    }
    
    /**
     * Get available times for scheduling in 30-minute intervals.
     *
     * @return array
     */
    public static function getAvailableTimes()
    {
        $times = [];
        
        // Generate times from 08:00 to 20:00 in 30-minute intervals
        for ($hour = 8; $hour <= 20; $hour++) {
            $formattedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $times[] = "{$formattedHour}:00";
            $times[] = "{$formattedHour}:30";
        }
        
        return $times;
    }
    
    /**
     * Get formatted schedule as a string.
     *
     * @return string|null
     */
    public function getFormattedScheduleAttribute()
    {
        if ($this->schedule_day && $this->schedule_time) {
            $time = $this->schedule_time instanceof \DateTime 
                ? $this->schedule_time->format('H:i') 
                : date('H:i', strtotime($this->schedule_time));
                
            return "{$this->schedule_day} at {$time}";
        }
        
        return null;
    }
}
