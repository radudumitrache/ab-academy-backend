<?php

namespace App\Helpers;

class ChoiceAnswerHelper
{
    /**
     * Parse a student's multiple-choice answer into the list of selected
     * variant indices.
     *
     * The student apps encode a selection as (see
     * `student frontend update/src/components/shared/formatAnswer.ts`):
     *   "2"           → [2]      single selection, legacy plain-index format
     *   '["0","2"]'   → [0, 2]   two or more selections, JSON array of strings
     *   "0,2"         → [0, 2]   defensive: comma separated
     *
     * Returns a de-duplicated, ascending list of non-negative integers.
     *
     * @return int[]
     */
    public static function indices(?string $answer): array
    {
        if ($answer === null || trim($answer) === '') {
            return [];
        }

        $trimmed = trim($answer);
        $decoded = json_decode($trimmed, true);

        $raw = is_array($decoded)
            ? $decoded
            : explode(',', $trimmed);

        $out = [];
        foreach ($raw as $value) {
            if (is_array($value) || !is_numeric(trim((string) $value))) {
                continue;
            }
            $idx = (int) trim((string) $value);
            if ($idx >= 0 && !in_array($idx, $out, true)) {
                $out[] = $idx;
            }
        }

        sort($out);

        return $out;
    }

    /**
     * Resolve a student's answer into the matching variant texts.
     * Indices with no matching variant are dropped.
     *
     * @param  string[]  $variants
     * @return string[]
     */
    public static function texts(array $variants, ?string $answer): array
    {
        $out = [];
        foreach (self::indices($answer) as $idx) {
            if (isset($variants[$idx])) {
                $out[] = $variants[$idx];
            }
        }

        return $out;
    }
}
