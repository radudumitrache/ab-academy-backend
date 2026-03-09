<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestionResponse extends Model
{
    use HasFactory;

    protected $primaryKey = 'response_id';

    protected $fillable = [
        'submission_id',
        'related_question',
        'related_student',
        'answer',
        'grade',
        'observation',
        'correction_file_path',
    ];

    public function submission()
    {
        return $this->belongsTo(TestSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'related_question', 'test_question_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'related_student');
    }
}
