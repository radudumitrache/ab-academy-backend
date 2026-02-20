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
        'normal_schedule',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'normal_schedule' => 'datetime',
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
}
