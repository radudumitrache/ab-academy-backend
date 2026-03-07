<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestWritingQuestion extends Model
{
    protected $table = 'test_writing_questions';

    protected $fillable = [
        'test_question_id',
        'sample_answer',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
