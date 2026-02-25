<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'homework_id',
        'student_id',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * All question responses that belong to this submission.
     */
    public function responses()
    {
        return $this->hasMany(QuestionResponse::class, 'submission_id');
    }

    /**
     * Responses grouped by section — loads responses with their question
     * so callers can group by question.section_id.
     */
    public function responsesWithQuestion()
    {
        return $this->hasMany(QuestionResponse::class, 'submission_id')
                    ->with('question');
    }
}
