<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestWordDerivationQuestion extends Model
{
    protected $table = 'test_word_derivation_questions';

    protected $fillable = [
        'test_question_id',
        'root_word',
        'sample_answer',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
