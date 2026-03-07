<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestTextCompletionQuestion extends Model
{
    protected $table = 'test_text_completion_questions';

    protected $fillable = [
        'test_question_id',
        'full_text',
        'correct_answers',
    ];

    protected $casts = [
        'correct_answers' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
