<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestCorrelationQuestion extends Model
{
    protected $table = 'test_correlation_questions';

    protected $fillable = [
        'test_question_id',
        'column_a',
        'column_b',
        'correct_pairs',
    ];

    protected $casts = [
        'column_a'      => 'array',
        'column_b'      => 'array',
        'correct_pairs' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
