<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestSection;
use App\Models\TypesOfTestQuestions\TestCorrelationQuestion;
use App\Models\TypesOfTestQuestions\TestCorrectQuestion;
use App\Models\TypesOfTestQuestions\TestGapFillQuestion;
use App\Models\TypesOfTestQuestions\TestMultipleChoiceQuestion;
use App\Models\TypesOfTestQuestions\TestReadingQuestion;
use App\Models\TypesOfTestQuestions\TestRephraseQuestion;
use App\Models\TypesOfTestQuestions\TestReplaceQuestion;
use App\Models\TypesOfTestQuestions\TestSpeakingQuestion;
use App\Models\TypesOfTestQuestions\TestTextCompletionQuestion;
use App\Models\TypesOfTestQuestions\TestWordDerivationQuestion;
use App\Models\TypesOfTestQuestions\TestWordFormationQuestion;
use App\Models\TypesOfTestQuestions\TestWritingQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestQuestionController extends Controller
{
    private function loadDetailRelation(TestQuestion $question): TestQuestion
    {
        $map = [
            'multiple_choice'           => 'multipleChoiceDetails',
            'reading_multiple_choice'   => 'multipleChoiceDetails',
            'listening_multiple_choice' => 'multipleChoiceDetails',
            'gap_fill'                  => 'gapFillDetails',
            'rephrase'                  => 'rephraseDetails',
            'word_formation'            => 'wordFormationDetails',
            'replace'                   => 'replaceDetails',
            'correct'                   => 'correctDetails',
            'word_derivation'           => 'wordDerivationDetails',
            'text_completion'           => 'textCompletionDetails',
            'correlation'               => 'correlationDetails',
            'reading_question'          => 'readingQuestionDetails',
            'writing_question'          => 'writingQuestionDetails',
            'speaking_question'         => 'speakingQuestionDetails',
        ];

        $relation = $map[$question->question_type] ?? null;
        if ($relation) { $question->load($relation); }

        return $question;
    }

    public function store(Request $request, $testId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $allTypes = array_unique(array_merge(...array_values(TestSection::ALLOWED_QUESTION_TYPES)));

        $validated = $request->validate([
            'section_id'                    => 'required|integer',
            'question_text'                 => 'required|string',
            'question_type'                 => ['required', Rule::in($allTypes)],
            'order'                         => 'nullable|integer|min:1',
            'instruction_files'             => 'nullable|array',
            'instruction_files.*'           => 'integer|exists:materials,material_id',
            'variants'                      => 'nullable|array',
            'variants.*'                    => 'string',
            'correct_variant'               => 'nullable|integer',
            'with_variants'                 => 'nullable|boolean',
            'correct_answers'               => 'nullable|array',
            'correct_answers.*'             => 'string',
            'sample_answer'                 => 'nullable|string',
            'base_word'                     => 'nullable|string',
            'root_word'                     => 'nullable|string',
            'original_text'                 => 'nullable|string',
            'incorrect_text'                => 'nullable|string',
            'full_text'                     => 'nullable|string',
            'column_a'                      => 'nullable|array',
            'column_a.*'                    => 'string',
            'column_b'                      => 'nullable|array',
            'column_b.*'                    => 'string',
            'correct_pairs'                 => 'nullable|array',
            'speaking_instruction_files'    => 'nullable|array',
            'speaking_instruction_files.*'  => 'integer|exists:materials,material_id',
        ]);

        $section = TestSection::where('test_id', $testId)->find($validated['section_id']);
        if (!$section) {
            return response()->json(['message' => 'Section not found on this test'], 404);
        }

        $allowed = TestSection::ALLOWED_QUESTION_TYPES[$section->section_type] ?? [];
        if (!in_array($validated['question_type'], $allowed)) {
            return response()->json([
                'message'       => "question_type '{$validated['question_type']}' is not allowed in a {$section->section_type} section",
                'allowed_types' => $allowed,
            ], 422);
        }

        $question = TestQuestion::create([
            'test_id'           => $testId,
            'test_section_id'   => $section->id,
            'question_text'     => $validated['question_text'],
            'question_type'     => $validated['question_type'],
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
        ]);

        $this->createDetailRecord($question, $validated['question_type'], $validated);

        DatabaseLog::logAction('create', TestQuestion::class, $question->test_question_id, "Question (type: {$question->question_type}) created for test #{$testId}");

        return response()->json([
            'message'  => 'Question created successfully',
            'question' => $this->loadDetailRelation($question->fresh()),
        ], 201);
    }

    public function update(Request $request, $testId, $questionId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $question = TestQuestion::where('test_id', $testId)->find($questionId);
        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $validated = $request->validate([
            'question_text'                 => 'sometimes|string',
            'order'                         => 'nullable|integer|min:1',
            'instruction_files'             => 'nullable|array',
            'instruction_files.*'           => 'integer|exists:materials,material_id',
            'variants'                      => 'nullable|array',
            'variants.*'                    => 'string',
            'correct_variant'               => 'nullable|integer',
            'with_variants'                 => 'nullable|boolean',
            'correct_answers'               => 'nullable|array',
            'correct_answers.*'             => 'string',
            'sample_answer'                 => 'nullable|string',
            'base_word'                     => 'nullable|string',
            'root_word'                     => 'nullable|string',
            'original_text'                 => 'nullable|string',
            'incorrect_text'                => 'nullable|string',
            'full_text'                     => 'nullable|string',
            'column_a'                      => 'nullable|array',
            'column_a.*'                    => 'string',
            'column_b'                      => 'nullable|array',
            'column_b.*'                    => 'string',
            'correct_pairs'                 => 'nullable|array',
            'speaking_instruction_files'    => 'nullable|array',
            'speaking_instruction_files.*'  => 'integer|exists:materials,material_id',
        ]);

        $baseFields = array_filter([
            'question_text'     => $validated['question_text'] ?? null,
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
        ], fn ($v) => !is_null($v));

        if (!empty($baseFields)) { $question->update($baseFields); }

        $this->updateDetailRecord($question, $question->question_type, $validated);

        DatabaseLog::logAction('update', TestQuestion::class, $question->test_question_id, "Question #{$questionId} updated for test #{$testId}");

        return response()->json([
            'message'  => 'Question updated successfully',
            'question' => $this->loadDetailRelation($question->fresh()),
        ]);
    }

    public function destroy($testId, $questionId)
    {
        if (!Test::find($testId)) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        $question = TestQuestion::where('test_id', $testId)->find($questionId);
        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $question->delete();

        DatabaseLog::logAction('delete', TestQuestion::class, $questionId, "Question #{$questionId} deleted from test #{$testId}");

        return response()->json(['message' => 'Question deleted successfully']);
    }

    private function createDetailRecord(TestQuestion $question, string $type, array $data): void
    {
        $qId = $question->test_question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice'])
                => TestMultipleChoiceQuestion::create(['test_question_id' => $qId, 'variants' => $data['variants'] ?? [], 'correct_variant' => $data['correct_variant'] ?? 0]),
            $type === 'gap_fill'        => TestGapFillQuestion::create(['test_question_id' => $qId, 'with_variants' => $data['with_variants'] ?? false, 'variants' => $data['variants'] ?? null, 'correct_answers' => $data['correct_answers'] ?? []]),
            $type === 'rephrase'        => TestRephraseQuestion::create(['test_question_id' => $qId, 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'word_formation'  => TestWordFormationQuestion::create(['test_question_id' => $qId, 'base_word' => $data['base_word'] ?? '', 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'replace'         => TestReplaceQuestion::create(['test_question_id' => $qId, 'original_text' => $data['original_text'] ?? '', 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'correct'         => TestCorrectQuestion::create(['test_question_id' => $qId, 'incorrect_text' => $data['incorrect_text'] ?? '', 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'word_derivation' => TestWordDerivationQuestion::create(['test_question_id' => $qId, 'root_word' => $data['root_word'] ?? '', 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'text_completion' => TestTextCompletionQuestion::create(['test_question_id' => $qId, 'full_text' => $data['full_text'] ?? '', 'correct_answers' => $data['correct_answers'] ?? []]),
            $type === 'correlation'     => TestCorrelationQuestion::create(['test_question_id' => $qId, 'column_a' => $data['column_a'] ?? [], 'column_b' => $data['column_b'] ?? [], 'correct_pairs' => $data['correct_pairs'] ?? []]),
            $type === 'reading_question'  => TestReadingQuestion::create(['test_question_id' => $qId, 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'writing_question'  => TestWritingQuestion::create(['test_question_id' => $qId, 'sample_answer' => $data['sample_answer'] ?? null]),
            $type === 'speaking_question' => TestSpeakingQuestion::create(['test_question_id' => $qId, 'instruction_files' => $data['speaking_instruction_files'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null]),
            default => null,
        };
    }

    private function updateDetailRecord(TestQuestion $question, string $type, array $data): void
    {
        $qId = $question->test_question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice']) => (function () use ($qId, $data) {
                $d = TestMultipleChoiceQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['variants' => $data['variants'] ?? null, 'correct_variant' => $data['correct_variant'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'gap_fill' => (function () use ($qId, $data) {
                $d = TestGapFillQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['with_variants' => $data['with_variants'] ?? null, 'variants' => $data['variants'] ?? null, 'correct_answers' => $data['correct_answers'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'rephrase' => (function () use ($qId, $data) {
                $d = TestRephraseQuestion::where('test_question_id', $qId)->first();
                if ($d && isset($data['sample_answer'])) $d->update(['sample_answer' => $data['sample_answer']]);
            })(),
            $type === 'word_formation' => (function () use ($qId, $data) {
                $d = TestWordFormationQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['base_word' => $data['base_word'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'replace' => (function () use ($qId, $data) {
                $d = TestReplaceQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['original_text' => $data['original_text'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'correct' => (function () use ($qId, $data) {
                $d = TestCorrectQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['incorrect_text' => $data['incorrect_text'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'word_derivation' => (function () use ($qId, $data) {
                $d = TestWordDerivationQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['root_word' => $data['root_word'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'text_completion' => (function () use ($qId, $data) {
                $d = TestTextCompletionQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['full_text' => $data['full_text'] ?? null, 'correct_answers' => $data['correct_answers'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'correlation' => (function () use ($qId, $data) {
                $d = TestCorrelationQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['column_a' => $data['column_a'] ?? null, 'column_b' => $data['column_b'] ?? null, 'correct_pairs' => $data['correct_pairs'] ?? null], fn($v) => !is_null($v)));
            })(),
            $type === 'reading_question' => (function () use ($qId, $data) {
                $d = TestReadingQuestion::where('test_question_id', $qId)->first();
                if ($d && isset($data['sample_answer'])) $d->update(['sample_answer' => $data['sample_answer']]);
            })(),
            $type === 'writing_question' => (function () use ($qId, $data) {
                $d = TestWritingQuestion::where('test_question_id', $qId)->first();
                if ($d && isset($data['sample_answer'])) $d->update(['sample_answer' => $data['sample_answer']]);
            })(),
            $type === 'speaking_question' => (function () use ($qId, $data) {
                $d = TestSpeakingQuestion::where('test_question_id', $qId)->first();
                if ($d) $d->update(array_filter(['instruction_files' => $data['speaking_instruction_files'] ?? null, 'sample_answer' => $data['sample_answer'] ?? null], fn($v) => !is_null($v)));
            })(),
            default => null,
        };
    }
}
