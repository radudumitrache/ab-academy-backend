<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GapFillQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
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
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
