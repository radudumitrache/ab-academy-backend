# Homework Management

Teachers can create, edit and delete homework assignments, build questions of various types within them, and assign them to students or groups.

---

## Homework Object

```json
{
  "id": 1,
  "homework_teacher": 4,
  "homework_title": "Unit 5 Practice",
  "homework_description": "Complete all sections before the due date.",
  "due_date": "2026-03-15",
  "people_assigned": [12, 15],
  "groups_assigned": [3],
  "date_created": "2026-02-25T10:00:00.000000Z",
  "created_at": "2026-02-25T10:00:00.000000Z",
  "updated_at": "2026-02-25T10:00:00.000000Z"
}
```

---

## Question Types

| `question_type` | Detail table | Section required? | Description |
|-----------------|-------------|-------------------|-------------|
| `multiple_choice` | `multiple_choice_questions` | No | Standard multiple choice |
| `gap_fill` | `gap_fill_questions` | No | Fill in the blank(s), optionally with variant hints |
| `rephrase` | `rephrase_questions` | No | Rewrite a sentence in a different way |
| `word_formation` | `word_formation_questions` | No | Form a word from a base word |
| `replace` | `replace_questions` | No | Replace part of a sentence |
| `correct` | `correct_questions` | No | Find and correct an error |
| `word_derivation` | `word_derivation_questions` | No | Derive a word from a root |
| `reading_multiple_choice` | `multiple_choice_questions` | Yes (reading) | MC question under a reading passage |
| `reading_question` | *(none — open text)* | Yes (reading) | Open question under a reading passage |
| `listening_multiple_choice` | `multiple_choice_questions` | Yes (listening) | MC question under an audio clip |
| `text_completion` | `text_completion_questions` | No | Fill blanks in a longer text (`___` marks blanks) |
| `correlation` | `correlation_questions` | No | Match items from column A to column B |

---

## List My Homework

- **URL**: `GET /api/teacher/homework`
- **Auth Required**: Yes

- **Success Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "count": 2,
  "homework": [ { ... } ]
}
```

---

## Get Single Homework

Returns homework with all questions and their type-specific detail records. Top-level questions, reading sections (with their questions), and listening sections (with their questions) are all included.

- **URL**: `GET /api/teacher/homework/{id}`
- **Auth Required**: Yes

- **Success Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "homework": {
    "id": 1,
    "homework_title": "Unit 5 Practice",
    "questions": [ { ... } ],
    "reading_sections": [
      {
        "id": 1,
        "title": "Passage A",
        "passage": "...",
        "order": 1,
        "questions": [ { ... } ]
      }
    ],
    "listening_sections": [ { ... } ]
  }
}
```

- **Errors**: `404` if not found or not owned by this teacher.

---

## Create Homework

- **URL**: `POST /api/teacher/homework`
- **Auth Required**: Yes
- **Request Body**:
```json
{
  "homework_title": "Unit 5 Practice",
  "homework_description": "Complete all sections.",
  "due_date": "2026-03-15",
  "people_assigned": [12, 15],
  "groups_assigned": [3]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `homework_title` | string | Yes | Max 255 characters |
| `homework_description` | string | No | |
| `due_date` | string | Yes | `YYYY-MM-DD` |
| `people_assigned` | array | No | Array of user IDs |
| `groups_assigned` | array | No | Array of group IDs — teacher must own them |

- **Success**: `201` with created homework object.

---

## Update Homework

- **URL**: `PUT /api/teacher/homework/{id}`
- **Auth Required**: Yes (owner only)
- **Request Body**: All fields optional (same as create).
- **Success**: `200` with updated homework.
- **Errors**: `404` not found / not owned, `422` validation.

---

## Delete Homework

- **URL**: `DELETE /api/teacher/homework/{id}`
- **Auth Required**: Yes (owner only)
- All questions and sections cascade-delete automatically.
- **Success**: `200 { "message": "Homework deleted successfully" }`
- **Errors**: `404` not found / not owned.

---

## Assign Students

Overwrites the current `people_assigned` and `groups_assigned` lists.

- **URL**: `POST /api/teacher/homework/{id}/assign`
- **Auth Required**: Yes (owner only)
- **Request Body**:
```json
{
  "people_assigned": [12, 15],
  "groups_assigned": [3]
}
```
- Teachers can only assign students from their own groups.
- **Success**: `200` with updated homework.

---

## Create a Question

- **URL**: `POST /api/teacher/homework/{homeworkId}/questions`
- **Auth Required**: Yes (homework owner only)

### Common fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `question_text` | string | Yes | The question prompt |
| `question_type` | string | Yes | One of the types in the table above |
| `order` | integer | No | Display order within homework |
| `instruction_files` | array | No | Array of URLs for instruction attachments |
| `section_id` | integer | Required for section types | ID of reading or listening section |

### Type-specific fields

**`multiple_choice` / `reading_multiple_choice` / `listening_multiple_choice`**
```json
{
  "variants": ["Option A", "Option B", "Option C", "Option D"],
  "correct_variant": 2
}
```
`correct_variant` is the 0-based index of the correct option.

**`gap_fill`**
```json
{
  "with_variants": true,
  "variants": ["run", "ran", "running"],
  "correct_answers": ["ran", "running"]
}
```
`with_variants`: whether hint options are shown. `correct_answers`: one string per blank in order.

**`rephrase`**
```json
{ "sample_answer": "He went to the store." }
```

**`word_formation`**
```json
{ "base_word": "create", "sample_answer": "creation" }
```

**`replace`**
```json
{ "original_text": "She go to school every day.", "sample_answer": "She goes to school every day." }
```

**`correct`**
```json
{ "incorrect_text": "He don't like coffee.", "sample_answer": "He doesn't like coffee." }
```

**`word_derivation`**
```json
{ "root_word": "happy", "sample_answer": "unhappiness" }
```

**`text_completion`**
```json
{
  "full_text": "The cat ___ on the mat. It ___ very comfortable.",
  "correct_answers": ["sat", "was"]
}
```
Blanks are marked with `___`. `correct_answers` maps to blanks in order.

**`correlation`**
```json
{
  "column_a": ["big", "fast", "cold"],
  "column_b": ["large", "hot", "quick"],
  "correct_pairs": [[0, 0], [1, 2], [2, 1]]
}
```
`correct_pairs`: array of `[column_a_index, column_b_index]` pairs.

**`reading_question`**
No type-specific fields. `section_id` is required (reading section).

- **Success**: `201` with question object including detail record.
- **Errors**: `404` homework not found, `422` validation.

---

## Update a Question

- **URL**: `PUT /api/teacher/homework/{homeworkId}/questions/{questionId}`
- **Auth Required**: Yes (homework owner only)
- All fields optional. Only sends the fields you want to change.
- Cannot change `question_type` on an existing question.
- **Success**: `200` with updated question + detail record.

---

## Delete a Question

- **URL**: `DELETE /api/teacher/homework/{homeworkId}/questions/{questionId}`
- **Auth Required**: Yes (homework owner only)
- Detail record is removed automatically (DB cascade).
- **Success**: `200 { "message": "Question deleted successfully" }`

---

## Create a Section (Reading or Listening)

Sections group questions under a shared passage or audio clip.

- **URL**: `POST /api/teacher/homework/{homeworkId}/sections`
- **Auth Required**: Yes (homework owner only)

### Reading section
```json
{
  "section_type": "reading",
  "title": "Passage A",
  "passage": "The industrial revolution began in Britain...",
  "order": 1
}
```

### Listening section
```json
{
  "section_type": "listening",
  "title": "Audio Clip 1",
  "audio_url": "https://example.com/audio/clip1.mp3",
  "transcript": "Optional transcript text here.",
  "order": 2
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `section_type` | string | Yes | `reading` or `listening` |
| `title` | string | No | |
| `order` | integer | No | Display order |
| `passage` | string | Required for reading | |
| `audio_url` | url | Required for listening | |
| `transcript` | string | No (listening only) | Optional transcript |

- **Success**: `201` with section object.

---

## Delete a Section

- **URL**: `DELETE /api/teacher/homework/{homeworkId}/sections/{sectionId}`
- **Auth Required**: Yes (homework owner only)
- **Request Body**: `{ "section_type": "reading" }` (or `"listening"`)
- All questions inside the section cascade-delete automatically.
- **Success**: `200 { "message": "Section deleted successfully" }`

---

## Full Workflow Example

1. `POST /api/teacher/homework` — create homework
2. `POST /api/teacher/homework/1/sections` — add a reading section (`section_type: reading`)
3. `POST /api/teacher/homework/1/questions` — add `reading_multiple_choice` question with `section_id: 1`
4. `POST /api/teacher/homework/1/questions` — add a top-level `gap_fill` question (no section_id)
5. `POST /api/teacher/homework/1/assign` — assign to groups/students
