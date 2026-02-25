<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultipleChoiceQuestion extends Model
{
    use HasFactory;

    protected $table = 'multiple_choice_questions';

    protected $fillable = [
        'question_id',
        'variants',
        'correct_variant',
    ];

    protected $casts = [
        'variants' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
