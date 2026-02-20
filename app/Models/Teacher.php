<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('role', function ($query) {
            $query->where('role', 'teacher');
        });

        static::creating(function ($model) {
            $model->role = 'teacher';
        });
    }
    
    /**
     * Get the groups that the teacher leads.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'group_teacher');
    }
    
    /**
     * Get the exams that the teacher has created.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'teacher_id');
    }
    
    /**
     * Get the count of students this teacher is teaching.
     */
    public function getStudentsCountAttribute()
    {
        $studentIds = [];
        
        // Get students from all groups
        foreach ($this->groups as $group) {
            foreach ($group->students as $student) {
                $studentIds[$student->id] = true;
            }
        }
        
        return count($studentIds);
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function isTeacher(): bool
    {
        return true;
    }

    public function isStudent(): bool
    {
        return false;
    }
}
