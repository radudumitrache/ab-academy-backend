<?php

namespace App\Models\TypesOfQuestions;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'sample_answer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
