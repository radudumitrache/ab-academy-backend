<?php

namespace App\Models;

use App\Models\TypesOfQuestions\MultipleChoiceQuestion;
use App\Models\TypesOfQuestions\GapFillQuestion;
use App\Models\TypesOfQuestions\RephraseQuestion;
use App\Models\TypesOfQuestions\WordFormationQuestion;
use App\Models\TypesOfQuestions\ReplaceQuestion;
use App\Models\TypesOfQuestions\CorrectQuestion;
use App\Models\TypesOfQuestions\WordDerivationQuestion;
use App\Models\TypesOfQuestions\TextCompletionQuestion;
use App\Models\TypesOfQuestions\CorrelationQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $primaryKey = 'question_id';

    protected $fillable = [
        'question_text',
        'homework_id',
        'question_type',
        'instruction_files',
        'order',
        'section_id',
        'section_type',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    // ── Homework relationship ─────────────────────────────────────────────────

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    // ── Section relationships ─────────────────────────────────────────────────

    public function readingSection()
    {
        return $this->belongsTo(ReadingSection::class, 'section_id');
    }

    public function listeningSection()
    {
        return $this->belongsTo(ListeningSection::class, 'section_id');
    }

    // ── Detail relationships (one per question type) ──────────────────────────

    public function multipleChoiceDetails()
    {
        return $this->hasOne(MultipleChoiceQuestion::class, 'question_id', 'question_id');
    }

    public function gapFillDetails()
    {
        return $this->hasOne(GapFillQuestion::class, 'question_id', 'question_id');
    }

    public function rephraseDetails()
    {
        return $this->hasOne(RephraseQuestion::class, 'question_id', 'question_id');
    }

    public function wordFormationDetails()
    {
        return $this->hasOne(WordFormationQuestion::class, 'question_id', 'question_id');
    }

    public function replaceDetails()
    {
        return $this->hasOne(ReplaceQuestion::class, 'question_id', 'question_id');
    }

    public function correctDetails()
    {
        return $this->hasOne(CorrectQuestion::class, 'question_id', 'question_id');
    }

    public function wordDerivationDetails()
    {
        return $this->hasOne(WordDerivationQuestion::class, 'question_id', 'question_id');
    }

    public function textCompletionDetails()
    {
        return $this->hasOne(TextCompletionQuestion::class, 'question_id', 'question_id');
    }

    public function correlationDetails()
    {
        return $this->hasOne(CorrelationQuestion::class, 'question_id', 'question_id');
    }

    // ── Student responses ─────────────────────────────────────────────────────

    public function responses()
    {
        return $this->hasMany(QuestionResponse::class, 'related_question', 'question_id');
    }
}
