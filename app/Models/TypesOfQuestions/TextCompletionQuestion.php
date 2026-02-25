<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextCompletionQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'full_text',
        'correct_answers',
    ];

    protected $casts = [
        'correct_answers' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
