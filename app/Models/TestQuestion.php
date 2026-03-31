<?php

namespace App\Models;

use App\Models\TypesOfTestQuestions\TestCorrelationQuestion;
use App\Models\TypesOfTestQuestions\TestCorrectQuestion;
use App\Models\TypesOfTestQuestions\TestGapFillQuestion;
use App\Models\TypesOfTestQuestions\TestMultipleChoiceQuestion;
use App\Models\TypesOfTestQuestions\TestReadingQuestion;
use App\Models\TypesOfTestQuestions\TestWritingQuestion;
use App\Models\TypesOfTestQuestions\TestSpeakingQuestion;
use App\Models\TypesOfTestQuestions\TestMixedQuestion;
use App\Models\TypesOfTestQuestions\TestRephraseQuestion;
use App\Models\TypesOfTestQuestions\TestReplaceQuestion;
use App\Models\TypesOfTestQuestions\TestTextCompletionQuestion;
use App\Models\TypesOfTestQuestions\TestWordDerivationQuestion;
use App\Models\TypesOfTestQuestions\TestWordFormationQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;

    protected $primaryKey = 'test_question_id';

    protected $fillable = [
        'test_id',
        'test_section_id',
        'question_text',
        'question_type',
        'instruction_files',
        'order',
    ];

    protected $casts = [
        'instruction_files' => 'array',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function section()
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }

    // ── Detail relationships ───────────────────────────────────────────────────

    public function multipleChoiceDetails()
    {
        return $this->hasOne(TestMultipleChoiceQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function gapFillDetails()
    {
        return $this->hasOne(TestGapFillQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function rephraseDetails()
    {
        return $this->hasOne(TestRephraseQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function wordFormationDetails()
    {
        return $this->hasOne(TestWordFormationQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function replaceDetails()
    {
        return $this->hasOne(TestReplaceQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function correctDetails()
    {
        return $this->hasOne(TestCorrectQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function wordDerivationDetails()
    {
        return $this->hasOne(TestWordDerivationQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function textCompletionDetails()
    {
        return $this->hasOne(TestTextCompletionQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function correlationDetails()
    {
        return $this->hasOne(TestCorrelationQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function readingQuestionDetails()
    {
        return $this->hasOne(TestReadingQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function writingQuestionDetails()
    {
        return $this->hasOne(TestWritingQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function speakingQuestionDetails()
    {
        return $this->hasOne(TestSpeakingQuestion::class, 'test_question_id', 'test_question_id');
    }

    public function mixedQuestionDetails()
    {
        return $this->hasOne(TestMixedQuestion::class, 'test_question_id', 'test_question_id');
    }
}
