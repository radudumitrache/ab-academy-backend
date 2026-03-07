<?php

namespace App\Models\TypesOfTestQuestions;

use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Model;

class TestSpeakingQuestion extends Model
{
    protected $table = 'test_speaking_questions';

    protected $fillable = [
        'test_question_id',
        'instruction_files',
        'sample_answer',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'test_question_id', 'test_question_id');
    }
}
