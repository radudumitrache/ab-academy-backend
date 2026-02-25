<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrelationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
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
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
