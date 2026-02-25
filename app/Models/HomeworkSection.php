<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkSection extends Model
{
    use HasFactory;

    const TYPES = ['GrammarAndVocabulary', 'Writing', 'Reading', 'Listening'];

    /**
     * Question types allowed per section type.
     */
    const ALLOWED_QUESTION_TYPES = [
        'GrammarAndVocabulary' => [
            'multiple_choice', 'gap_fill', 'rephrase', 'word_formation',
            'replace', 'correct', 'word_derivation', 'text_completion', 'correlation',
        ],
        'Writing' => [
            'rephrase', 'word_formation', 'replace', 'correct', 'word_derivation',
        ],
        'Reading' => [
            'reading_multiple_choice', 'reading_question',
        ],
        'Listening' => [
            'listening_multiple_choice', 'text_completion',
        ],
    ];

    protected $fillable = [
        'homework_id',
        'section_type',
        'title',
        'instruction_files',
        'passage',
        'audio_url',
        'transcript',
        'order',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'section_id')->orderBy('order');
    }
}
