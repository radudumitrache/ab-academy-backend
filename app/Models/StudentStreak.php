<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentStreak extends Model
{
    protected $fillable = [
        'student_id',
        'current_streak',
        'longest_streak',
        'last_submission_at',
    ];

    protected $casts = [
        'last_submission_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
