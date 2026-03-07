<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestMultipleChoiceQuestion extends Model
{
    protected $table = 'test_multiple_choice_questions';

    protected $fillable = [
        'test_question_id',
        'variants',
        'correct_variant',
    ];

    protected $casts = [
        'variants' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
