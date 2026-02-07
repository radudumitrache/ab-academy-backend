<?php

namespace App\Models;

class Admin extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('role', function ($query) {
            $query->where('role', 'admin');
        });

        static::creating(function ($model) {
            $model->role = 'admin';
        });
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function isTeacher(): bool
    {
        return false;
    }

    public function isStudent(): bool
    {
        return false;
    }
}
