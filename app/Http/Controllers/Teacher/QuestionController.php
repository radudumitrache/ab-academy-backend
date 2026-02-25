<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkSection;
use App\Models\Question;
use App\Models\TypesOfQuestions\CorrelationQuestion;
use App\Models\TypesOfQuestions\CorrectQuestion;
use App\Models\TypesOfQuestions\GapFillQuestion;
use App\Models\TypesOfQuestions\MultipleChoiceQuestion;
use App\Models\TypesOfQuestions\ReadingQuestion;
use App\Models\TypesOfQuestions\RephraseQuestion;
use App\Models\TypesOfQuestions\ReplaceQuestion;
use App\Models\TypesOfQuestions\TextCompletionQuestion;
use App\Models\TypesOfQuestions\WordDerivationQuestion;
use App\Models\TypesOfQuestions\WordFormationQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    private function findOwnedHomework($homeworkId)
    {
        return Homework::where('homework_teacher', Auth::id())->find($homeworkId);
    }

    private function loadDetailRelation(Question $question): Question
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
        ];

        $relation = $map[$question->question_type] ?? null;
        if ($relation) {
            $question->load($relation);
        }

        return $question;
    }

    /**
     * Create a question inside a section.
     * section_id is required — questions always belong to a section.
     */
    public function store(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $allTypes = array_unique(array_merge(...array_values(HomeworkSection::ALLOWED_QUESTION_TYPES)));

        $validated = $request->validate([
            'section_id'          => 'required|integer',
            'question_text'       => 'required|string',
            'question_type'       => ['required', Rule::in($allTypes)],
            'order'               => 'nullable|integer|min:1',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'url',
            'variants'            => 'nullable|array',
            'variants.*'          => 'string',
            'correct_variant'     => 'nullable|integer',
            'with_variants'       => 'nullable|boolean',
            'correct_answers'     => 'nullable|array',
            'correct_answers.*'   => 'string',
            'sample_answer'       => 'nullable|string',
            'base_word'           => 'nullable|string',
            'root_word'           => 'nullable|string',
            'original_text'       => 'nullable|string',
            'incorrect_text'      => 'nullable|string',
            'full_text'           => 'nullable|string',
            'column_a'            => 'nullable|array',
            'column_a.*'          => 'string',
            'column_b'            => 'nullable|array',
            'column_b.*'          => 'string',
            'correct_pairs'       => 'nullable|array',
        ]);

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($validated['section_id']);
        if (!$section) {
            return response()->json(['message' => 'Section not found on this homework'], 404);
        }

        $allowed = HomeworkSection::ALLOWED_QUESTION_TYPES[$section->section_type] ?? [];
        if (!in_array($validated['question_type'], $allowed)) {
            return response()->json([
                'message'       => "question_type '{$validated['question_type']}' is not allowed in a {$section->section_type} section",
                'allowed_types' => $allowed,
            ], 422);
        }

        $question = Question::create([
            'homework_id'       => $homeworkId,
            'section_id'        => $section->id,
            'question_text'     => $validated['question_text'],
            'question_type'     => $validated['question_type'],
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
        ]);

        $this->createDetailRecord($question, $validated['question_type'], $validated);

        return response()->json([
            'message'  => 'Question created successfully',
            'question' => $this->loadDetailRelation($question->fresh()),
        ], 201);
    }

    /**
     * Update a question's base fields and/or detail record.
     */
    public function update(Request $request, $homeworkId, $questionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $question = Question::where('homework_id', $homeworkId)->find($questionId);
        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $validated = $request->validate([
            'question_text'       => 'sometimes|string',
            'order'               => 'nullable|integer|min:1',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'url',
            'variants'            => 'nullable|array',
            'variants.*'          => 'string',
            'correct_variant'     => 'nullable|integer',
            'with_variants'       => 'nullable|boolean',
            'correct_answers'     => 'nullable|array',
            'correct_answers.*'   => 'string',
            'sample_answer'       => 'nullable|string',
            'base_word'           => 'nullable|string',
            'root_word'           => 'nullable|string',
            'original_text'       => 'nullable|string',
            'incorrect_text'      => 'nullable|string',
            'full_text'           => 'nullable|string',
            'column_a'            => 'nullable|array',
            'column_a.*'          => 'string',
            'column_b'            => 'nullable|array',
            'column_b.*'          => 'string',
            'correct_pairs'       => 'nullable|array',
        ]);

        $baseFields = array_filter([
            'question_text'     => $validated['question_text'] ?? null,
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
        ], fn ($v) => !is_null($v));

        if (!empty($baseFields)) {
            $question->update($baseFields);
        }

        $this->updateDetailRecord($question, $question->question_type, $validated);

        return response()->json([
            'message'  => 'Question updated successfully',
            'question' => $this->loadDetailRelation($question->fresh()),
        ]);
    }

    /**
     * Delete a question. Detail record is removed by DB cascade.
     */
    public function destroy($homeworkId, $questionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $question = Question::where('homework_id', $homeworkId)->find($questionId);
        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $question->delete();

        return response()->json(['message' => 'Question deleted successfully']);
    }

    private function createDetailRecord(Question $question, string $type, array $data): void
    {
        $qId = $question->question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice'])
                => MultipleChoiceQuestion::create([
                    'question_id'     => $qId,
                    'variants'        => $data['variants'] ?? [],
                    'correct_variant' => $data['correct_variant'] ?? 0,
                ]),

            $type === 'gap_fill'
                => GapFillQuestion::create([
                    'question_id'     => $qId,
                    'with_variants'   => $data['with_variants'] ?? false,
                    'variants'        => $data['variants'] ?? null,
                    'correct_answers' => $data['correct_answers'] ?? [],
                ]),

            $type === 'rephrase'
                => RephraseQuestion::create([
                    'question_id'   => $qId,
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            $type === 'word_formation'
                => WordFormationQuestion::create([
                    'question_id'   => $qId,
                    'base_word'     => $data['base_word'] ?? '',
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            $type === 'replace'
                => ReplaceQuestion::create([
                    'question_id'   => $qId,
                    'original_text' => $data['original_text'] ?? '',
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            $type === 'correct'
                => CorrectQuestion::create([
                    'question_id'    => $qId,
                    'incorrect_text' => $data['incorrect_text'] ?? '',
                    'sample_answer'  => $data['sample_answer'] ?? null,
                ]),

            $type === 'word_derivation'
                => WordDerivationQuestion::create([
                    'question_id'   => $qId,
                    'root_word'     => $data['root_word'] ?? '',
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            $type === 'text_completion'
                => TextCompletionQuestion::create([
                    'question_id'     => $qId,
                    'full_text'       => $data['full_text'] ?? '',
                    'correct_answers' => $data['correct_answers'] ?? [],
                ]),

            $type === 'correlation'
                => CorrelationQuestion::create([
                    'question_id'   => $qId,
                    'column_a'      => $data['column_a'] ?? [],
                    'column_b'      => $data['column_b'] ?? [],
                    'correct_pairs' => $data['correct_pairs'] ?? [],
                ]),

            $type === 'reading_question'
                => ReadingQuestion::create([
                    'question_id'   => $qId,
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            default => null,
        };
    }

    private function updateDetailRecord(Question $question, string $type, array $data): void
    {
        $qId = $question->question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice']) => (function () use ($qId, $data) {
                $detail = MultipleChoiceQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'variants'        => $data['variants'] ?? null,
                        'correct_variant' => $data['correct_variant'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'gap_fill' => (function () use ($qId, $data) {
                $detail = GapFillQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'with_variants'   => $data['with_variants'] ?? null,
                        'variants'        => $data['variants'] ?? null,
                        'correct_answers' => $data['correct_answers'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'rephrase' => (function () use ($qId, $data) {
                $detail = RephraseQuestion::where('question_id', $qId)->first();
                if ($detail && isset($data['sample_answer'])) {
                    $detail->update(['sample_answer' => $data['sample_answer']]);
                }
            })(),

            $type === 'word_formation' => (function () use ($qId, $data) {
                $detail = WordFormationQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'base_word'     => $data['base_word'] ?? null,
                        'sample_answer' => $data['sample_answer'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'replace' => (function () use ($qId, $data) {
                $detail = ReplaceQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'original_text' => $data['original_text'] ?? null,
                        'sample_answer' => $data['sample_answer'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'correct' => (function () use ($qId, $data) {
                $detail = CorrectQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'incorrect_text' => $data['incorrect_text'] ?? null,
                        'sample_answer'  => $data['sample_answer'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'word_derivation' => (function () use ($qId, $data) {
                $detail = WordDerivationQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'root_word'     => $data['root_word'] ?? null,
                        'sample_answer' => $data['sample_answer'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'text_completion' => (function () use ($qId, $data) {
                $detail = TextCompletionQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'full_text'       => $data['full_text'] ?? null,
                        'correct_answers' => $data['correct_answers'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'correlation' => (function () use ($qId, $data) {
                $detail = CorrelationQuestion::where('question_id', $qId)->first();
                if ($detail) {
                    $detail->update(array_filter([
                        'column_a'      => $data['column_a'] ?? null,
                        'column_b'      => $data['column_b'] ?? null,
                        'correct_pairs' => $data['correct_pairs'] ?? null,
                    ], fn ($v) => !is_null($v)));
                }
            })(),

            $type === 'reading_question' => (function () use ($qId, $data) {
                $detail = ReadingQuestion::where('question_id', $qId)->first();
                if ($detail && isset($data['sample_answer'])) {
                    $detail->update(['sample_answer' => $data['sample_answer']]);
                }
            })(),

            default => null,
        };
    }
}
