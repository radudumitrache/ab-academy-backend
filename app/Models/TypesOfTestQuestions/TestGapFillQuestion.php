<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestGapFillQuestion extends Model
{
    protected $table = 'test_gap_fill_questions';

    protected $fillable = [
        'test_question_id',
        'with_variants',
        'variants',
        'correct_answers',
    ];

    protected $casts = [
        'with_variants'   => 'boolean',
        'variants'        => 'array',
        'correct_answers' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
