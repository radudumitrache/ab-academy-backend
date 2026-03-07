<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Model;

class SpeakingQuestion extends Model
{
    protected $table = 'speaking_questions';

    protected $fillable = [
        'question_id',
        'instruction_files',
        'sample_answer',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
