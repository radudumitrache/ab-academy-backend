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
use App\Models\TypesOfQuestions\SpeakingQuestion;
use App\Models\TypesOfQuestions\TextCompletionQuestion;
use App\Models\TypesOfQuestions\WordDerivationQuestion;
use App\Models\TypesOfQuestions\WordFormationQuestion;
use App\Models\TypesOfQuestions\WritingQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    private function findOwnedHomework($homeworkId)
    {
        return Homework::where('homework_teacher', Auth::id())->find($homeworkId);
    }

    /**
     * List all sections of a homework (with question counts).
     */
    public function index($homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $sections = HomeworkSection::where('homework_id', $homeworkId)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return response()->json([
            'message'  => 'Sections retrieved successfully',
            'sections' => $sections,
        ]);
    }

    /**
     * Create a section on a homework owned by the teacher.
     *
     * section_type: GrammarAndVocabulary | Writing | Reading | Listening | Speaking
     *
     * Reading sections accept:  passage (optional)
     * Listening sections accept: audio_url (optional), audio_material_id (optional), transcript (optional)
     * All sections accept: title, instruction_text, instruction_files, order
     */
    public function store(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $validated = $request->validate([
            'section_type'        => ['required', Rule::in(HomeworkSection::TYPES)],
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            // Reading-specific
            'passage'             => 'nullable|string',
            // Listening-specific
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);



        $section = HomeworkSection::create([
            'homework_id'       => $homeworkId,
            'section_type'      => $validated['section_type'],
            'title'             => $validated['title'] ?? null,
            'instruction_text'  => $validated['instruction_text'] ?? null,
            'instruction_files' => $validated['instruction_files'] ?? null,
            'order'             => $validated['order'] ?? null,
            'passage'           => $validated['passage'] ?? null,
            'audio_url'         => $validated['audio_url'] ?? null,
            'audio_material_id' => $validated['audio_material_id'] ?? null,
            'transcript'        => $validated['transcript'] ?? null,
        ]);

        return response()->json([
            'message' => 'Section created successfully',
            'section' => $section,
        ], 201);
    }

    /**
     * Update a section. Only the homework owner may edit.
     */
    public function update(Request $request, $homeworkId, $sectionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $validated = $request->validate([
            'title'               => 'nullable|string|max:255',
            'instruction_text'    => 'nullable|string',
            'instruction_files'   => 'nullable|array',
            'instruction_files.*' => 'integer|exists:materials,material_id',
            'order'               => 'nullable|integer|min:1',
            'passage'             => 'nullable|string',
            'audio_url'           => 'nullable|url',
            'audio_material_id'   => 'nullable|integer|exists:materials,material_id',
            'transcript'          => 'nullable|string',
        ]);

        $section->update(array_filter($validated, fn ($v) => !is_null($v)));

        return response()->json([
            'message' => 'Section updated successfully',
            'section' => $section->fresh(),
        ]);
    }

    /**
     * Create a section together with all its questions in one request.
     *
     * Accepts the same section fields as store(), plus:
     *   questions[]: array of question objects, each with the same fields as QuestionController::store()
     *                (question_text, question_type, order, and all type-specific detail fields)
     *
     * All inserts are wrapped in a transaction — if any question fails, nothing is saved.
     *
     * Used by the n8n AI-parsing flow to turn a reading PDF into structured homework in one HTTP call.
     */
    public function batchStore(Request $request, $homeworkId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $allTypes = array_unique(array_merge(...array_values(HomeworkSection::ALLOWED_QUESTION_TYPES)));

        $validated = $request->validate([
            'section_type'                                 => ['required', Rule::in(HomeworkSection::TYPES)],
            'title'                                        => 'nullable|string|max:255',
            'instruction_text'                             => 'nullable|string',
            'instruction_files'                            => 'nullable|array',
            'instruction_files.*'                          => 'integer|exists:materials,material_id',
            'order'                                        => 'nullable|integer|min:1',
            'passage'                                      => 'nullable|string',
            'audio_url'                                    => 'nullable|url',
            'audio_material_id'                            => 'nullable|integer|exists:materials,material_id',
            'transcript'                                   => 'nullable|string',
            // questions array
            'questions'                                    => 'nullable|array',
            'questions.*.question_text'                    => 'required_with:questions|string',
            'questions.*.question_type'                    => ['required_with:questions', Rule::in($allTypes)],
            'questions.*.order'                            => 'nullable|integer|min:1',
            'questions.*.instruction_files'                => 'nullable|array',
            'questions.*.instruction_files.*'              => 'integer|exists:materials,material_id',
            // multiple_choice / reading_multiple_choice / listening_multiple_choice
            'questions.*.variants'                         => 'nullable|array',
            'questions.*.variants.*'                       => 'string',
            'questions.*.correct_variant'                  => 'nullable',
            // gap_fill
            'questions.*.with_variants'                    => 'nullable|boolean',
            'questions.*.correct_answers'                  => 'nullable|array',
            'questions.*.correct_answers.*'                => 'string',
            // rephrase / word_formation / replace / correct / word_derivation / reading_question / writing_question / speaking_question
            'questions.*.sample_answer'                    => 'nullable|string',
            'questions.*.base_word'                        => 'nullable|string',
            'questions.*.root_word'                        => 'nullable|string',
            'questions.*.original_text'                    => 'nullable|string',
            'questions.*.incorrect_text'                   => 'nullable|string',
            // text_completion
            'questions.*.full_text'                        => 'nullable|string',
            // correlation
            'questions.*.column_a'                         => 'nullable|array',
            'questions.*.column_a.*'                       => 'string',
            'questions.*.column_b'                         => 'nullable|array',
            'questions.*.column_b.*'                       => 'string',
            'questions.*.correct_pairs'                    => 'nullable|array',
            // speaking_question
            'questions.*.speaking_instruction_files'       => 'nullable|array',
            'questions.*.speaking_instruction_files.*'     => 'integer|exists:materials,material_id',
        ]);

        $sectionType = $validated['section_type'];
        $allowedForSection = HomeworkSection::ALLOWED_QUESTION_TYPES[$sectionType] ?? [];

        // Validate each question type is allowed in this section type before touching the DB
        foreach ($validated['questions'] ?? [] as $index => $q) {
            if (!in_array($q['question_type'], $allowedForSection)) {
                return response()->json([
                    'message'       => "questions.{$index}.question_type '{$q['question_type']}' is not allowed in a {$sectionType} section",
                    'allowed_types' => $allowedForSection,
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($homeworkId, $validated, $sectionType) {
            $section = HomeworkSection::create([
                'homework_id'       => $homeworkId,
                'section_type'      => $sectionType,
                'title'             => $validated['title'] ?? null,
                'instruction_text'  => $validated['instruction_text'] ?? null,
                'instruction_files' => $validated['instruction_files'] ?? null,
                'order'             => $validated['order'] ?? null,
                'passage'           => $validated['passage'] ?? null,
                'audio_url'         => $validated['audio_url'] ?? null,
                'audio_material_id' => $validated['audio_material_id'] ?? null,
                'transcript'        => $validated['transcript'] ?? null,
            ]);

            $createdQuestions = [];
            foreach ($validated['questions'] ?? [] as $qData) {
                $question = Question::create([
                    'homework_id'       => $homeworkId,
                    'section_id'        => $section->id,
                    'question_text'     => $qData['question_text'],
                    'question_type'     => $qData['question_type'],
                    'order'             => $qData['order'] ?? null,
                    'instruction_files' => $qData['instruction_files'] ?? null,
                ]);

                $this->createQuestionDetail($question, $qData['question_type'], $qData);
                $createdQuestions[] = $question->question_id;
            }

            return ['section' => $section, 'question_ids' => $createdQuestions];
        });

        $section = $result['section']->load('questions');

        return response()->json([
            'message' => 'Section created successfully with questions',
            'section' => $section,
        ], 201);
    }

    private function createQuestionDetail(Question $question, string $type, array $data): void
    {
        $qId = $question->question_id;

        match (true) {
            in_array($type, ['multiple_choice', 'reading_multiple_choice', 'listening_multiple_choice'])
                => MultipleChoiceQuestion::create([
                    'question_id'     => $qId,
                    'variants'        => $data['variants'] ?? [],
                    'correct_variant' => is_array($data['correct_variant'] ?? null)
                        ? $data['correct_variant']
                        : (isset($data['correct_variant']) ? [$data['correct_variant']] : []),
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
                    'question_id'    => $qId,
                    'sample_answer'  => $data['sample_answer'] ?? null,
                    'correct_answers' => $data['correct_answers'] ?? null,
                ]),

            $type === 'writing_question'
                => WritingQuestion::create([
                    'question_id'   => $qId,
                    'sample_answer' => $data['sample_answer'] ?? null,
                ]),

            $type === 'speaking_question'
                => SpeakingQuestion::create([
                    'question_id'       => $qId,
                    'instruction_files' => $data['speaking_instruction_files'] ?? null,
                    'sample_answer'     => $data['sample_answer'] ?? null,
                ]),

            default => null,
        };
    }

    /**
     * Delete a section. Questions inside cascade-delete automatically.
     */
    public function destroy($homeworkId, $sectionId)
    {
        $homework = $this->findOwnedHomework($homeworkId);
        if (!$homework) {
            return response()->json(['message' => 'Homework not found'], 404);
        }

        $section = HomeworkSection::where('homework_id', $homeworkId)->find($sectionId);
        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }
}
