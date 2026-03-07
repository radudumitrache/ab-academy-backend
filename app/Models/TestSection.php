<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSection extends Model
{
    use HasFactory;

    const TYPES = ['GrammarAndVocabulary', 'Writing', 'Reading', 'Listening', 'Speaking'];

    /**
     * Question types allowed per section type (same rules as homework sections).
     */
    const ALLOWED_QUESTION_TYPES = [
        'GrammarAndVocabulary' => [
            'multiple_choice', 'gap_fill', 'rephrase', 'word_formation',
            'replace', 'correct', 'word_derivation', 'text_completion', 'correlation',
        ],
        'Writing' => [
            'rephrase', 'word_formation', 'replace', 'correct', 'word_derivation', 'writing_question',
        ],
        'Reading' => [
            'reading_multiple_choice', 'reading_question',
        ],
        'Listening' => [
            'listening_multiple_choice', 'text_completion',
        ],
        'Speaking' => [
            'speaking_question',
        ],
    ];

    protected $fillable = [
        'test_id',
        'section_type',
        'title',
        'instruction_text',
        'instruction_files',
        'passage',
        'audio_url',
        'audio_material_id',
        'transcript',
        'order',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function questions()
    {
        return $this->hasMany(TestQuestion::class, 'test_section_id')->orderBy('order');
    }

    public function audioMaterial()
    {
        return $this->belongsTo(Material::class, 'audio_material_id');
    }
}
