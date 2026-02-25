# Homework Management

Teachers build homework by following this four-step flow:

1. **Create homework** — title, description, due date
2. **Assign students** — choose students or groups
3. **Create sections** — add GrammarAndVocabulary / Writing / Reading / Listening sections, each with optional instructions / files
4. **Add questions** — add questions to a section (each question must target a specific section)

---

## Creation Flow

```
POST /api/teacher/homework                        → create homework
POST /api/teacher/homework/{id}/assign            → assign students/groups
POST /api/teacher/homework/{id}/sections          → add sections
POST /api/teacher/homework/{id}/questions         → add questions to a section
```

---

## Section Types

| `section_type` | Allowed question types |
|----------------|----------------------|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation` |
| `Reading` | `reading_multiple_choice`, `reading_question` |
| `Listening` | `listening_multiple_choice`, `text_completion` |

Sections have:
- `title` (optional)
- `instruction_files` — JSON array of URLs (optional, for PDFs, images, etc.)
- **Reading only**: `passage` (required) — the text students read
- **Listening only**: `audio_url` (required), `transcript` (optional)

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
  "date_created": "2026-02-25T10:00:00.000000Z"
}
```

---

## Section Object

```json
{
  "id": 2,
  "homework_id": 1,
  "section_type": "Reading",
  "title": "Passage A",
  "instruction_files": ["https://example.com/instructions.pdf"],
  "passage": "The industrial revolution began in Britain...",
  "audio_url": null,
  "transcript": null,
  "order": 1
}
```

---

## Homework Endpoints

### List My Homework

`GET /api/teacher/homework`

Returns all homework created by the authenticated teacher, newest first.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "count": 2,
  "homework": [ { ... } ]
}
```

---

### Get Single Homework (with all sections and questions)

`GET /api/teacher/homework/{id}`

Returns the homework with all sections eagerly loaded. Each section includes its questions with their type-specific detail records.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "homework": {
    "id": 1,
    "homework_title": "Unit 5 Practice",
    "sections": [
      {
        "id": 2,
        "section_type": "GrammarAndVocabulary",
        "title": "Part 1",
        "instruction_files": [],
        "order": 1,
        "questions": [
          {
            "question_id": 5,
            "question_type": "multiple_choice",
            "question_text": "Choose the correct form.",
            "multiple_choice_details": {
              "variants": ["run", "ran", "running"],
              "correct_variant": 1
            }
          }
        ]
      }
    ]
  }
}
```

**Errors**: `404` if not found or not owned by this teacher.

---

### Create Homework

`POST /api/teacher/homework`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `homework_title` | string | Yes | Max 255 characters |
| `homework_description` | string | No | |
| `due_date` | string | Yes | `YYYY-MM-DD` |

**Response** `201` with created homework object.

---

### Update Homework

`PUT /api/teacher/homework/{id}` — owner only, all fields optional (same as create).

---

### Delete Homework

`DELETE /api/teacher/homework/{id}` — owner only. Sections and questions cascade-delete.

---

### Assign Students

`POST /api/teacher/homework/{id}/assign` — overwrites current assignment.

```json
{
  "people_assigned": [12, 15],
  "groups_assigned": [3]
}
```

Teachers can only assign students/groups they own. The HomeworkObserver fires a notification to all assigned students.

---

## Section Endpoints

### List Sections

`GET /api/teacher/homework/{homeworkId}/sections`

Returns all sections of a homework with question counts.

---

### Create Section

`POST /api/teacher/homework/{homeworkId}/sections`

```json
{
  "section_type": "Reading",
  "title": "Passage A",
  "instruction_files": ["https://example.com/passage_notes.pdf"],
  "passage": "The industrial revolution began...",
  "order": 1
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `section_type` | string | Yes | `GrammarAndVocabulary`, `Writing`, `Reading`, or `Listening` |
| `title` | string | No | |
| `instruction_files` | array | No | Array of URLs |
| `order` | integer | No | Display order |
| `passage` | string | Required for `Reading` | |
| `audio_url` | url | Required for `Listening` | |
| `transcript` | string | No (`Listening` only) | |

**Response** `201` with created section object.

---

### Update Section

`PUT /api/teacher/homework/{homeworkId}/sections/{sectionId}` — all fields optional, cannot change `section_type`.

---

### Delete Section

`DELETE /api/teacher/homework/{homeworkId}/sections/{sectionId}` — questions inside cascade-delete.

---

## Question Endpoints

Questions **must** belong to a section (`section_id` is required). The question type must be allowed for the target section type — the API returns a `422` with `allowed_types` if not.

### Create Question

`POST /api/teacher/homework/{homeworkId}/questions`

**Common fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `section_id` | integer | Yes | ID of the target section |
| `question_text` | string | Yes | The question prompt |
| `question_type` | string | Yes | See section type table above |
| `order` | integer | No | Display order within section |
| `instruction_files` | array | No | Array of URLs for attachments on this question |

**Type-specific fields** (same as before):

| Type | Extra fields |
|------|-------------|
| `multiple_choice` / `reading_multiple_choice` / `listening_multiple_choice` | `variants` (array of strings), `correct_variant` (0-based index) |
| `gap_fill` | `with_variants` (bool), `variants` (array, optional), `correct_answers` (array of strings) |
| `rephrase` / `reading_question` | `sample_answer` (string) |
| `word_formation` | `base_word`, `sample_answer` |
| `replace` | `original_text`, `sample_answer` |
| `correct` | `incorrect_text`, `sample_answer` |
| `word_derivation` | `root_word`, `sample_answer` |
| `text_completion` | `full_text` (blanks as `___`), `correct_answers` (array) |
| `correlation` | `column_a` (array), `column_b` (array), `correct_pairs` ([[a_idx, b_idx], ...]) |

**Response** `201` with question + detail record.

**Errors**:
- `404` — homework or section not found
- `422` — invalid question type for this section type, includes `allowed_types`

---

### Update Question

`PUT /api/teacher/homework/{homeworkId}/questions/{questionId}` — all fields optional. Cannot change `question_type` or `section_id`.

---

### Delete Question

`DELETE /api/teacher/homework/{homeworkId}/questions/{questionId}` — detail record cascade-deleted.

---

## Full Workflow Example

```
# 1. Create homework
POST /api/teacher/homework
{ "homework_title": "Unit 5", "due_date": "2026-03-15" }

# 2. Assign
POST /api/teacher/homework/1/assign
{ "groups_assigned": [3] }

# 3. Add a Grammar section
POST /api/teacher/homework/1/sections
{ "section_type": "GrammarAndVocabulary", "title": "Part 1", "order": 1 }

# 4. Add a Reading section with a passage
POST /api/teacher/homework/1/sections
{ "section_type": "Reading", "title": "Text A", "passage": "Once upon a time...", "order": 2 }

# 5. Add a gap_fill question to the Grammar section (section id=1)
POST /api/teacher/homework/1/questions
{
  "section_id": 1,
  "question_type": "gap_fill",
  "question_text": "Fill in the blanks.",
  "correct_answers": ["went", "seen"]
}

# 6. Add a reading_question to the Reading section (section id=2)
POST /api/teacher/homework/1/questions
{
  "section_id": 2,
  "question_type": "reading_question",
  "question_text": "What is the main theme of the text?",
  "sample_answer": "The main theme is..."
}
```
