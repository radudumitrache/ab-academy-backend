# Test Management

Tests follow the same structure as homework. The creation flow is identical:

1. **Create test** — title, description, due date
2. **Assign students** — choose students or groups
3. **Create sections** — add GrammarAndVocabulary / Writing / Reading / Listening sections
4. **Add questions** — add questions to a section

---

## Creation Flow

```
POST /api/teacher/tests                         → create test
POST /api/teacher/tests/{id}/assign             → assign students/groups
POST /api/teacher/tests/{id}/sections           → add sections
POST /api/teacher/tests/{id}/questions          → add questions to a section
```

---

## Section Types

| `section_type` | Allowed question types |
|----------------|----------------------|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation` |
| `Reading` | `reading_multiple_choice`, `reading_question` |
| `Listening` | `listening_multiple_choice`, `text_completion` |

Sections support:
- `title` (optional)
- `instruction_files` — JSON array of **Material IDs** resolved to signed URLs on fetch
- **Reading only**: `passage` (required)
- **Listening only**: `audio_url` (external) or `audio_material_id` (GCS Material ID); at least one required

---

## Test Object

```json
{
  "id": 1,
  "test_teacher": 4,
  "test_title": "Unit 5 Final Test",
  "test_description": "Covers grammar and reading comprehension.",
  "due_date": "2026-04-01",
  "people_assigned": [12, 15],
  "groups_assigned": [3],
  "date_created": "2026-03-07T10:00:00.000000Z"
}
```

---

## Test Endpoints

### List My Tests

`GET /api/teacher/tests`

Returns all tests created by the authenticated teacher, newest first. Includes `all_questions_count`.

**Response** `200`:
```json
{
  "message": "Tests retrieved successfully",
  "count": 2,
  "tests": [ { ... } ]
}
```

---

### Get Single Test (with all sections and questions)

`GET /api/teacher/tests/{id}`

Returns the test with all sections eagerly loaded. Each section includes questions with their type-specific detail records. Material IDs in `instruction_files` are resolved to 60-minute signed GCS URLs.

**Response** `200`:
```json
{
  "message": "Test retrieved successfully",
  "test": {
    "id": 1,
    "test_title": "Unit 5 Final Test",
    "sections": [
      {
        "id": 2,
        "section_type": "Reading",
        "title": "Passage A",
        "instruction_files": [5],
        "instruction_file_urls": [
          { "material_id": 5, "url": "https://storage.googleapis.com/...?X-Goog-Signature=..." }
        ],
        "passage": "The industrial revolution began...",
        "audio_material_id": null,
        "order": 1,
        "questions": [
          {
            "test_question_id": 3,
            "question_type": "reading_multiple_choice",
            "question_text": "What triggered the industrial revolution?",
            "multiple_choice_details": {
              "variants": ["Steam power", "Electricity", "Wind power"],
              "correct_variant": 0
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

### Create Test

`POST /api/teacher/tests`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `test_title` | string | Yes | Max 255 characters |
| `test_description` | string | No | |
| `due_date` | string | Yes | `YYYY-MM-DD` |

**Response** `201` with created test object.

---

### Update Test

`PUT /api/teacher/tests/{id}` — owner only, all fields optional (same as create).

---

### Delete Test

`DELETE /api/teacher/tests/{id}` — owner only. Sections and questions cascade-delete.

---

### Assign Students

`POST /api/teacher/tests/{id}/assign` — overwrites current assignment.

```json
{
  "people_assigned": [12, 15],
  "groups_assigned": [3]
}
```

Teachers can only assign students/groups they own.

---

## Section Endpoints

### List Sections

`GET /api/teacher/tests/{testId}/sections`

Returns all sections with question counts.

---

### Create Section

`POST /api/teacher/tests/{testId}/sections`

```json
{
  "section_type": "Reading",
  "title": "Passage A",
  "instruction_files": [5, 7],
  "passage": "The industrial revolution began...",
  "order": 1
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `section_type` | string | Yes | `GrammarAndVocabulary`, `Writing`, `Reading`, or `Listening` |
| `title` | string | No | |
| `instruction_files` | array | No | Array of **Material IDs** (integers) |
| `order` | integer | No | Display order |
| `passage` | string | Required for `Reading` | |
| `audio_url` | url | Required for `Listening` (if `audio_material_id` not provided) | External audio URL |
| `audio_material_id` | integer | Required for `Listening` (if `audio_url` not provided) | Material ID of a GCS-hosted audio file |
| `transcript` | string | No (`Listening` only) | |

**Response** `201` with created section object.

---

### Update Section

`PUT /api/teacher/tests/{testId}/sections/{sectionId}` — all fields optional, cannot change `section_type`.

---

### Delete Section

`DELETE /api/teacher/tests/{testId}/sections/{sectionId}` — questions inside cascade-delete.

---

## Question Endpoints

Questions **must** belong to a section (`section_id` is required). The question type must be allowed for the target section type.

### Create Question

`POST /api/teacher/tests/{testId}/questions`

**Common fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `section_id` | integer | Yes | ID of the target section |
| `question_text` | string | Yes | The question prompt |
| `question_type` | string | Yes | See section type table above |
| `order` | integer | No | Display order within section |
| `instruction_files` | array | No | Array of **Material IDs** (integers) for attachments |

**Type-specific fields:**

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
- `404` — test or section not found
- `422` — invalid question type for this section type, includes `allowed_types`

---

### Update Question

`PUT /api/teacher/tests/{testId}/questions/{questionId}` — all fields optional. Cannot change `question_type` or `section_id`.

---

### Delete Question

`DELETE /api/teacher/tests/{testId}/questions/{questionId}` — detail record cascade-deleted.

---

## Full Workflow Example

```
# 1. Create test
POST /api/teacher/tests
{ "test_title": "Unit 5 Final", "due_date": "2026-04-01" }

# 2. Assign
POST /api/teacher/tests/1/assign
{ "groups_assigned": [3] }

# 3. Add a Grammar section
POST /api/teacher/tests/1/sections
{ "section_type": "GrammarAndVocabulary", "title": "Part 1", "order": 1 }

# 4. Add a Reading section
POST /api/teacher/tests/1/sections
{ "section_type": "Reading", "title": "Text A", "passage": "Once upon a time...", "order": 2 }

# 5. Add a multiple_choice question to the Grammar section (section id=1)
POST /api/teacher/tests/1/questions
{
  "section_id": 1,
  "question_type": "multiple_choice",
  "question_text": "Choose the correct form of the verb.",
  "variants": ["go", "went", "gone"],
  "correct_variant": 1
}

# 6. Add a reading_multiple_choice to the Reading section (section id=2)
POST /api/teacher/tests/1/questions
{
  "section_id": 2,
  "question_type": "reading_multiple_choice",
  "question_text": "What is the main theme of the passage?",
  "variants": ["Adventure", "Loss", "Triumph"],
  "correct_variant": 2
}
```
