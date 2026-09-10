<?php

namespace App\Services;

use App\Helpers\ChoiceAnswerHelper;
use App\Models\Question;
use App\Models\TestQuestion;

class AutoGradingService
{
    /**
     * Compute an automatic grade for a test question response.
     *
     * @param  int         $questionId  test_question_id
     * @param  string|null $answer      raw stored answer string
     * @return string|null  grade string, or null if question type is not auto-gradable
     */
    public static function gradeTestResponse(int $questionId, ?string $answer): ?string
    {
        $question = TestQuestion::with([
            'multipleChoiceDetails',
            'gapFillDetails',
            'textCompletionDetails',
            'correlationDetails',
        ])->find($questionId);

        if (!$question) {
            return null;
        }

        return self::compute($question->question_type, $answer, $question);
    }

    /**
     * Compute an automatic grade for a homework question response.
     *
     * @param  int         $questionId  question_id
     * @param  string|null $answer      raw stored answer string
     * @return string|null  grade string, or null if question type is not auto-gradable
     */
    public static function gradeHomeworkResponse(int $questionId, ?string $answer): ?string
    {
        $question = Question::with([
            'multipleChoiceDetails',
            'gapFillDetails',
            'textCompletionDetails',
            'correlationDetails',
        ])->find($questionId);

        if (!$question) {
            return null;
        }

        return self::compute($question->question_type, $answer, $question);
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private static function compute(string $type, ?string $answer, object $question): ?string
    {
        return match ($type) {
            'multiple_choice',
            'reading_multiple_choice',
            'listening_multiple_choice' => self::gradeMultipleChoice($answer, $question->multipleChoiceDetails),
            'gap_fill'         => self::gradeKeyedAnswers($answer, $question->gapFillDetails?->correct_answers ?? []),
            'text_completion'  => self::gradeKeyedAnswers($answer, $question->textCompletionDetails?->correct_answers ?? []),
            'correlation'      => self::gradeCorrelation($answer, $question->correlationDetails?->correct_pairs ?? []),
            default            => null,
        };
    }

    /**
     * Multiple choice: the student submits either a single index ("1") or,
     * when several variants are ticked, a JSON array of indices ('["1","3"]').
     * correct_variant is an array of correct indices.
     * Grade: "correct" only on an exact set match, otherwise "incorrect".
     */
    private static function gradeMultipleChoice(?string $answer, ?object $details): string
    {
        if ($details === null) {
            return 'incorrect';
        }

        $correctVariants = $details->correct_variant ?? [];

        $studentIdxs = ChoiceAnswerHelper::indices($answer);

        if (empty($studentIdxs)) {
            return 'incorrect';
        }

        $correctIdxs = array_values(array_unique(array_map('intval', (array) $correctVariants)));
        sort($correctIdxs);

        return $studentIdxs === $correctIdxs ? 'correct' : 'incorrect';
    }

    /**
     * Gap fill / text completion: student submits JSON like {"0":"steam","1":"coal"}.
     * correct_answers is a positional array ["steam", "coal"].
     * Grade: "x / total".
     */
    private static function gradeKeyedAnswers(?string $answer, array $correctAnswers): string
    {
        $total = count($correctAnswers);

        if ($total === 0) {
            return '0 / 0';
        }

        if ($answer === null || trim($answer) === '') {
            return "0 / {$total}";
        }

        $studentMap = json_decode($answer, true);

        if (!is_array($studentMap)) {
            return "0 / {$total}";
        }

        $correct = 0;
        foreach ($correctAnswers as $i => $expected) {
            $given = trim((string) ($studentMap[(string) $i] ?? ''));
            if (mb_strtolower($given) === mb_strtolower(trim((string) $expected))) {
                $correct++;
            }
        }

        return "{$correct} / {$total}";
    }

    /**
     * Correlation: student submits JSON like {"0":"1","1":"2"} (A-index => B-index).
     * correct_pairs is an array of [a_idx, b_idx] pairs.
     * Grade: "x / total".
     */
    private static function gradeCorrelation(?string $answer, array $correctPairs): string
    {
        $total = count($correctPairs);

        if ($total === 0) {
            return '0 / 0';
        }

        if ($answer === null || trim($answer) === '') {
            return "0 / {$total}";
        }

        $studentMap = json_decode($answer, true);

        if (!is_array($studentMap)) {
            return "0 / {$total}";
        }

        // Build a lookup from the student map: a_idx (int) => b_idx (int)
        $studentPairs = [];
        foreach ($studentMap as $aIdx => $bIdx) {
            $studentPairs[(int) $aIdx] = (int) $bIdx;
        }

        $correct = 0;
        foreach ($correctPairs as $pair) {
            // pair can be [a_idx, b_idx] (array) or {"0": a_idx, "1": b_idx} (object cast to array)
            $pair = (array) $pair;
            $aIdx = (int) ($pair[0] ?? $pair['0'] ?? -1);
            $bIdx = (int) ($pair[1] ?? $pair['1'] ?? -2);

            if (isset($studentPairs[$aIdx]) && $studentPairs[$aIdx] === $bIdx) {
                $correct++;
            }
        }

        return "{$correct} / {$total}";
    }
}
