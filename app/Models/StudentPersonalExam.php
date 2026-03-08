<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPersonalExam extends Model
{
    protected $table = 'student_personal_exams';

    protected $fillable = [
        'student_id',
        'name',
        'exam_type',
        'date',
        'score',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
