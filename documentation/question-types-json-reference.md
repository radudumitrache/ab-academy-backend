# Question Types — JSON Payload Reference

All fields are sent **flat** at the top level of the request body (never nested under a `details` object).

---

## Common Fields (all question types)

| Field | Type | Required (store) | Notes |
|---|---|---|---|
| `section_id` | integer | ✅ store only | ID of the section this question belongs to |
| `question_type` | string | ✅ | See allowed types per section below |
| `question_text` | string | ❌ nullable | The question prompt shown to the student |
| `order` | integer | ❌ nullable | Display order, min 0 |
| `instruction_files` | integer[] | ❌ nullable | Array of material IDs attached to the question |

---

## Section Types & Allowed Question Types

| Section Type | Allowed `question_type` values |
|---|---|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `writing_question` |
| `Reading` | `reading_multiple_choice`, `reading_question`, `gap_fill`, `text_completion`, `correlation` |
| `Listening` | `listening_multiple_choice`, `text_completion`, `gap_fill` |
| `Speaking` | `speaking_question` |
| `Mixed` | `mixed_question` |

---

## Question Type Payloads

### `multiple_choice` / `reading_multiple_choice` / `listening_multiple_choice`

The student picks one option from a list. `correct_variant` is the **0-based index** of the correct option in `variants`.

```json
{
  "section_id": 1,
  "question_type": "multiple_choice",
  "question_text": "Choose the correct answer: I ___ a student.",
  "order": 0,
  "variants": ["am", "be", "is", "are"],
  "correct_variant": 0
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `variants` | string[] | The answer options |
| `correct_variant` | integer | 0-based index of the correct option |

---

### `gap_fill`

The student fills in one or more blanks. Mark blanks in `question_text` with `___` or any convention agreed with the frontend.

```json
{
  "section_id": 1,
  "question_type": "gap_fill",
  "question_text": "She ___ to school every day.",
  "order": 1,
  "with_variants": false,
  "correct_answers": ["goes"]
}
```

With variants (student picks from a word bank):

```json
{
  "section_id": 1,
  "question_type": "gap_fill",
  "question_text": "She ___ to school every day.",
  "order": 1,
  "with_variants": true,
  "variants": ["go", "goes", "going"],
  "correct_answers": ["goes"]
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `with_variants` | boolean | `true` = show word bank to student |
| `variants` | string[] | Word bank options (only when `with_variants: true`) |
| `correct_answers` | string[] | One entry per blank, in order |

---

### `rephrase`

The student rewrites a sentence using a given word/structure.

```json
{
  "section_id": 1,
  "question_type": "rephrase",
  "question_text": "Although it was raining, they went out. (DESPITE)",
  "order": 2,
  "sample_answer": "Despite the rain, they went out."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `sample_answer` | string | Model answer shown after submission |

---

### `word_formation`

The student forms a new word from a base word to complete a sentence.

```json
{
  "section_id": 1,
  "question_type": "word_formation",
  "question_text": "The ___ of the project was delayed. (COMPLETE)",
  "order": 3,
  "base_word": "COMPLETE",
  "sample_answer": "completion"
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `base_word` | string | The root word shown to the student |
| `sample_answer` | string | Model answer |

---

### `replace`

The student replaces a word or phrase in a given text.

```json
{
  "section_id": 1,
  "question_type": "replace",
  "question_text": "Replace the underlined word with a synonym.",
  "order": 4,
  "original_text": "She was very happy about the news.",
  "sample_answer": "delighted / pleased / thrilled"
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `original_text` | string | The sentence containing the word to replace |
| `sample_answer` | string | Model answer |

---

### `correct`

The student finds and corrects errors in a piece of text.

```json
{
  "section_id": 1,
  "question_type": "correct",
  "question_text": "Find and correct the mistake in the sentence below.",
  "order": 5,
  "incorrect_text": "She don't like coffee.",
  "sample_answer": "She doesn't like coffee."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `incorrect_text` | string | The text containing the error |
| `sample_answer` | string | Corrected version |

---

### `word_derivation`

The student derives a form of a root word to complete a sentence.

```json
{
  "section_id": 1,
  "question_type": "word_derivation",
  "question_text": "Use the correct form of the word in brackets: He is very ___ (CREATE).",
  "order": 6,
  "root_word": "CREATE",
  "sample_answer": "creative"
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `root_word` | string | The root word shown to the student |
| `sample_answer` | string | Model answer |

---

### `text_completion`

The student completes a longer text with missing words. `correct_answers` maps to gaps in order.

```json
{
  "section_id": 1,
  "question_type": "text_completion",
  "question_text": "Complete the text with the correct words.",
  "order": 7,
  "full_text": "London is the ___ of England. It is a very ___ city.",
  "correct_answers": ["capital", "beautiful"]
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `full_text` | string | The full passage with gaps marked |
| `correct_answers` | string[] | Answers for each gap in order |

---

### `correlation`

The student matches items in Column A to items in Column B. `correct_pairs` is an array of `[indexA, indexB]` pairs (0-based).

```json
{
  "section_id": 1,
  "question_type": "correlation",
  "question_text": "Match the words with their definitions.",
  "order": 8,
  "column_a": ["happy", "sad", "angry"],
  "column_b": ["feeling sorrow", "feeling joy", "feeling rage"],
  "correct_pairs": [[0, 1], [1, 0], [2, 2]]
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `column_a` | string[] | Left-side items |
| `column_b` | string[] | Right-side items |
| `correct_pairs` | array of [int, int] | Each pair is `[indexA, indexB]` (0-based) |

---

### `reading_question`

An open-ended comprehension question. The passage is on the **section**, not the question.

```json
{
  "section_id": 1,
  "question_type": "reading_question",
  "question_text": "Why did the author decide to travel to Antarctica?",
  "order": 9,
  "sample_answer": "The author wanted to study climate change firsthand."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `sample_answer` | string | Model answer shown after grading |

---

### `writing_question`

A free-writing task. No type-specific fields beyond `sample_answer`.

```json
{
  "section_id": 1,
  "question_type": "writing_question",
  "question_text": "Write an essay of 200–250 words about the impact of social media.",
  "order": 10,
  "sample_answer": "Social media has transformed the way we communicate..."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `sample_answer` | string | Model answer / marking guide |

---

### `speaking_question`

A speaking task. Supports its own separate instruction files (`speaking_instruction_files`) independent of the question's `instruction_files`.

```json
{
  "section_id": 1,
  "question_type": "speaking_question",
  "question_text": "Describe the image below and give your opinion.",
  "order": 11,
  "speaking_instruction_files": [42, 43],
  "sample_answer": "The image shows a busy market scene..."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `speaking_instruction_files` | integer[] | Material IDs for audio/visual prompts specific to speaking |
| `sample_answer` | string | Model answer |

---

### `mixed_question`

Used in Mixed sections. Accepts a file upload from the student (handled separately via multipart form). Only `sample_answer` is stored server-side.

```json
{
  "section_id": 1,
  "question_type": "mixed_question",
  "question_text": "Listen to the recording and answer the questions below.",
  "order": 12,
  "sample_answer": "Optional model answer or marking notes."
}
```

| Extra Field | Type | Notes |
|---|---|---|
| `sample_answer` | string | Optional model answer |

---

## Notes

- **Store** (`POST /homework/{id}/questions` or `POST /tests/{id}/questions`): `section_id` and `question_type` are required. All other fields are optional.
- **Update** (`PUT /homework/{id}/questions/{qid}` or `PUT /tests/{id}/questions/{qid}`): Only send fields you want to change. `question_type` cannot be changed after creation.
- `instruction_files` attaches materials (images, PDFs) visible above the question for all types.
- `speaking_instruction_files` is **only** for `speaking_question` and stores materials in the speaking detail record separately.
