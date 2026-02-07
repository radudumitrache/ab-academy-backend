<?php

namespace App\Models;

class Student extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('role', function ($query) {
            $query->where('role', 'student');
        });

        static::creating(function ($model) {
            $model->role = 'student';
        });
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function isTeacher(): bool
    {
        return false;
    }

    public function isStudent(): bool
    {
        return true;
    }
}
