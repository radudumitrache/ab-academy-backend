<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Model;

class WritingQuestion extends Model
{
    protected $table = 'writing_questions';

    protected $fillable = [
        'question_id',
        'sample_answer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
