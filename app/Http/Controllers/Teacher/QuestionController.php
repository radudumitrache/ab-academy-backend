<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\ListeningSection;
use App\Models\Question;
use App\Models\ReadingSection;
use App\Models\TypesOfQuestions\CorrelationQuestion;
use App\Models\TypesOfQuestions\CorrectQuestion;
use App\Models\TypesOfQuestions\GapFillQuestion;
use App\Models\TypesOfQuestions\MultipleChoiceQuestion;
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
    // ─────────────────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────────────────

    const TYPES = [
        'multiple_choice',
        'gap_fill',
        'rephrase',
        'word_formation',
        'replace',
        'correct',
        'word_derivation',
        'reading_multiple_choice',
        'reading_question',
        'listening_multiple_choice',
        'text_completion',
        'correlation',
    ];

    // Types that require a section_id
    const SECTION_TYPES = [
        'reading_multiple_choice',
        'reading_question',
        'listening_multiple_choice',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function findOwnedHomework($homeworkId)
    {
        return Homework::where('homework_teacher', Auth::id())->find($homeworkId);
    }

    private function loadDetailRelation(Question $question): Question
    {
        $map = [
            'multiple_choice'          => 'multipleChoiceDetails',
            'reading_multiple_choice'  => 'multipleChoiceDetails',
            'listening_multiple_choice' => 'multipleChoiceDetails',
            'gap_fill'                 => 'gapFillDetails',
            'rephrase'                 => 'rephraseDetails',
            'word_formation'           => 'wordFormationDetails',
            'replace'                  => 'replaceDetails',
            'correct'                  => 'correctDetails',
            'word_derivation'          => 'wordDerivationDetails',
            'text_completion'          => 'textCompletionDetails',
            'correlation'              => 'correlationDetails',
        ];

        $relation = $map[$question->question_type] ?? null;
        if ($relation) {
            $question->load($relation);
        }

        return $question;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Questions CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a question on a homework owned by the teacher.
     */
    public function store(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'question_text'      => 'required|string',
            'question_type'      => ['required', Rule::in(self::TYPES)],
            'order'              => 'nullable|integer|min:1',
            'instruction_files'  => 'nullable|array',
            'instruction_files.*' => 'url',
            'section_id'         => 'nullable|integer',
            // ── type-specific fields ──────────────────────────────────────────
            // multiple_choice / reading_multiple_choice / listening_multiple_choice
            'variants'           => 'nullable|array',
            'variants.*'         => 'string',
            'correct_variant'    => 'nullable|integer',
            // gap_fill
            'with_variants'      => 'nullable|boolean',
            'correct_answers'    => 'nullable|array',
            'correct_answers.*'  => 'string',
            // rephrase / word_formation / replace / correct / word_derivation
            'sample_answer'      => 'nullable|string',
            'base_word'          => 'nullable|string',
            'root_word'          => 'nullable|string',
            'original_text'      => 'nullable|string',
            'incorrect_text'     => 'nullable|string',
            // text_completion
            'full_text'          => 'nullable|string',
            // correlation
            'column_a'           => 'nullable|array',
            'column_a.*'         => 'string',
            'column_b'           => 'nullable|array',
            'column_b.*'         => 'string',
            'correct_pairs'      => 'nullable|array',
        ]);

        $type = $validated['question_type'];

        // Validate section_id when required
        if (in_array($type, self::SECTION_TYPES)) {
            if (empty($validated['section_id'])) {
                return response()->json([
                    'message' => "question_type '{$type}' requires a section_id",
                ], 422);
            }

            $sectionType = str_starts_with($type, 'listening') ? 'listening' : 'reading';

            $sectionModel = $sectionType === 'reading' ? ReadingSection::class : ListeningSection::class;
            $section = $sectionModel::where('homework_id', $homeworkId)->find($validated['section_id']);

            if (!$section) {
                return response()->json(['message' => 'Section not found on this homework'], 404);
            }
        }

        // Build base question
        $question = Question::create([
            'homework_id'       => $homeworkId,
            'question_text'     => $validated['question_text'],
            'question_type'     => $type,
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
            'section_id'        => $validated['section_id'] ?? null,
            'section_type'      => isset($validated['section_id'])
                                    ? (str_starts_with($type, 'listening') ? 'listening' : 'reading')
                                    : null,
        ]);

        // Create the detail record for this question type
        $this->createDetailRecord($question, $type, $validated);

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
            // type-specific update fields (all optional)
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

        // Update base question fields
        $baseFields = array_filter([
            'question_text'     => $validated['question_text'] ?? null,
            'order'             => $validated['order'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
        ], fn ($v) => !is_null($v));

        if (!empty($baseFields)) {
            $question->update($baseFields);
        }

        // Update type-specific detail record
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

    // ─────────────────────────────────────────────────────────────────────────
    // Sections CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a reading or listening section on a homework.
     */
    public function storeSection(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'section_type' => ['required', Rule::in(['reading', 'listening'])],
            'title'        => 'nullable|string|max:255',
            'order'        => 'nullable|integer|min:1',
            // reading
            'passage'      => 'nullable|string',
            // listening
            'audio_url'    => 'nullable|url',
            'transcript'   => 'nullable|string',
        ]);

        if ($validated['section_type'] === 'reading') {
            $request->validate(['passage' => 'required|string']);

            $section = ReadingSection::create([
                'homework_id' => $homeworkId,
                'title'       => $validated['title'] ?? null,
                'passage'     => $validated['passage'],
                'order'       => $validated['order'] ?? null,
            ]);
        } else {
            $request->validate(['audio_url' => 'required|url']);

            $section = ListeningSection::create([
                'homework_id' => $homeworkId,
                'title'       => $validated['title'] ?? null,
                'audio_url'   => $validated['audio_url'],
                'transcript'  => $validated['transcript'] ?? null,
                'order'       => $validated['order'] ?? null,
            ]);
        }

        return response()->json([
            'message' => ucfirst($validated['section_type']) . ' section created successfully',
            'section' => $section,
        ], 201);
    }

    /**
     * Delete a reading or listening section.
     * Questions inside are removed by DB cascade.
     */
    public function destroySection(Request $request, $homeworkId, $sectionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $request->validate(['section_type' => ['required', Rule::in(['reading', 'listening'])]]);

        if ($request->section_type === 'reading') {
            $section = ReadingSection::where('homework_id', $homeworkId)->find($sectionId);
        } else {
            $section = ListeningSection::where('homework_id', $homeworkId)->find($sectionId);
        }

        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Detail record creation / update
    // ─────────────────────────────────────────────────────────────────────────

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
                    'question_id'  => $qId,
                    'column_a'     => $data['column_a'] ?? [],
                    'column_b'     => $data['column_b'] ?? [],
                    'correct_pairs' => $data['correct_pairs'] ?? [],
                ]),

            // reading_question has no detail table
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

            default => null,
        };
    }
}
