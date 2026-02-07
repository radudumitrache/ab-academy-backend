<?php

namespace App\Models;

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
