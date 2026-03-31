# Test Management

Tests follow the same structure as homework. The creation flow is identical:

1. **Create test** — title, description, due date
2. **Assign students** — choose students or groups
3. **Create sections** — add GrammarAndVocabulary / Writing / Reading / Listening / Speaking sections
4. **Add questions** — add questions to a section

---

## Creation Flow

```
POST /api/teacher/tests                              → create test
POST /api/teacher/tests/{id}/assign                  → assign students/groups
POST /api/teacher/tests/{id}/sections                → add a section
POST /api/teacher/tests/{id}/sections/batch          → add a section + all its questions in one call
POST /api/teacher/tests/{id}/questions               → add a question to an existing section
```

---

## Section Types

| `section_type` | Allowed question types |
|----------------|----------------------|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `writing_question` |
| `Reading` | `reading_multiple_choice`, `reading_question`, `gap_fill`, `text_completion`, `correlation` |
| `Listening` | `listening_multiple_choice`, `text_completion`, `gap_fill` |
| `Speaking` | `speaking_question` |
| `Mixed` | `mixed_question` |

**`mixed_question`** — open-ended question where the student submits a long text response **or** uploads a file (up to 50 MB). Supports an optional `sample_answer` field for teacher reference.

Sections support:
- `title` (optional)
- `instruction_text` (optional) — large text field for written instructions shown to students
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

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `people_assigned` | array | No | Array of user IDs |
| `groups_assigned` | array | No | Array of group IDs |
| `is_global` | boolean | No | When `true`, all current and future students can access this test; defaults to `false` |

```json
{
  "people_assigned": [12, 15],
  "groups_assigned": [3],
  "is_global": false
}
```

When `is_global` is `true`, the test is visible to every student on the platform — including users who register after the test is created. Omitting `is_global` from the request resets it to `false`. Teachers can only assign their own tests.

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
| `section_type` | string | Yes | `GrammarAndVocabulary`, `Writing`, `Reading`, `Listening`, or `Speaking` |
| `title` | string | No | |
| `instruction_text` | string | No | Large text shown as written instructions to students |
| `instruction_files` | array | No | Array of **Material IDs** (integers) |
| `order` | integer | No | Display order |
| `passage` | string | Required for `Reading` | |
| `audio_url` | url | Required for `Listening` (if `audio_material_id` not provided) | External audio URL |
| `audio_material_id` | integer | Required for `Listening` (if `audio_url` not provided) | Material ID of a GCS-hosted audio file |
| `transcript` | string | No (`Listening` only) | |

**Response** `201` with created section object.

---

### Create Section + Questions in One Request (Batch)

`POST /api/teacher/tests/{testId}/sections/batch`

Creates a section and all its questions in a single atomic transaction. Used by the n8n AI parsing flow to turn a PDF into structured test content in one HTTP call. If any question fails validation, nothing is saved.

Accepts the same section fields as **Create Section**, plus a `questions` array:

```json
{
  "section_type": "Reading",
  "title": "Reading Passage 1",
  "passage": "There are few places in the world...",
  "order": 1,
  "questions": [
    {
      "question_type": "reading_multiple_choice",
      "question_text": "What triggered the industrial revolution?",
      "order": 1,
      "variants": ["Steam power", "Electricity", "Wind power"],
      "correct_variant": 0
    },
    {
      "question_type": "reading_question",
      "question_text": "What is the main theme of the passage?",
      "order": 2,
      "sample_answer": "The main theme is..."
    }
  ]
}
```

Each question object accepts the same fields as the individual question create endpoint. The question type must be allowed for the section type — if not, the API returns `422` with `allowed_types` before touching the database.

**Response** `201`:
```json
{
  "message": "Section created successfully with questions",
  "section": {
    "id": 2,
    "section_type": "Reading",
    "questions": [ { ... }, { ... } ]
  }
}
```

**Errors**:
- `404` — test not found or not owned by this teacher
- `422` — invalid section type, invalid question type for section, or validation failure

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
| `rephrase` / `reading_question` / `writing_question` | `sample_answer` (string) |
| `word_formation` | `base_word`, `sample_answer` |
| `replace` | `original_text`, `sample_answer` |
| `correct` | `incorrect_text`, `sample_answer` |
| `word_derivation` | `root_word`, `sample_answer` |
| `text_completion` | `full_text` (blanks as `___`), `correct_answers` (array) |
| `correlation` | `column_a` (array), `column_b` (array), `correct_pairs` ([[a_idx, b_idx], ...]) |
| `speaking_question` | `speaking_instruction_files` (array of Material IDs, optional), `sample_answer` (string, optional) |

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

## Submission Endpoints

Teachers can view and grade student submissions for tests they own. Only submissions with `status = "submitted"` are returned by the list endpoint.

### List Submissions

`GET /api/teacher/tests/{testId}/submissions`

Returns all submitted submissions for the given test (owner only).

**Response** `200`:
```json
{
  "message": "Submissions retrieved successfully",
  "count": 2,
  "submissions": [
    {
      "id": 7,
      "test_id": 1,
      "student_id": 12,
      "status": "submitted",
      "submitted_at": "2026-03-10T15:00:00.000000Z",
      "grade": null,
      "observation": null,
      "student": { "id": 12, "username": "student1", "email": "s1@example.com" },
      "responses": [ { ... } ]
    }
  ]
}
```

**Errors**:
- `404` — test not found or not owned by this teacher

---

### Get Single Submission

`GET /api/teacher/tests/{testId}/submissions/{submissionId}`

Returns a single submission with all student responses and question details.

**Response** `200`:
```json
{
  "message": "Submission retrieved successfully",
  "submission": {
    "id": 7,
    "test_id": 1,
    "student_id": 12,
    "status": "submitted",
    "submitted_at": "2026-03-10T15:00:00.000000Z",
    "grade": null,
    "observation": null,
    "student": { "id": 12, "username": "student1", "email": "s1@example.com" },
    "responses": [
      {
        "response_id": 33,
        "submission_id": 7,
        "related_question": 3,
        "answer": "1",
        "answer_text": "went",
        "correct_answer": "went",
        "grade": null,
        "observation": null,
        "correction_file_path": null,
        "question": { "test_question_id": 3, "question_type": "multiple_choice", "..." : "..." }
      },
      {
        "response_id": 34,
        "submission_id": 7,
        "related_question": 4,
        "answer": "house",
        "answer_text": null,
        "correct_answer": ["house", "House"],
        "grade": null,
        "observation": null,
        "correction_file_path": null,
        "question": { "test_question_id": 4, "question_type": "gap_fill", "..." : "..." }
      }
    ]
  }
}
```

Each response object includes two resolved fields:

| Field | Description |
|-------|-------------|
| `answer_text` | For `multiple_choice` only — the variant string the student selected. `null` for all other types. |
| `correct_answer` | The expected answer for question types that define one. `null` for open-ended types (`writing_question`, `speaking_question`, `reading_question`). |

`correct_answer` by question type:

| Question type | Value |
|---|---|
| `multiple_choice` | String — the correct variant text |
| `gap_fill` / `text_completion` | Array of accepted strings |
| `correlation` | Array of correct pairs |
| `correct` / `word_formation` / `rephrase` / `replace` / `word_derivation` | String — sample answer |

**Errors**:
- `404` — test not found, not owned by teacher, or submission not found

---

### Grade a Submission

`PATCH /api/teacher/tests/{testId}/submissions/{submissionId}/grade`

Saves a grade and/or observation on a submitted test. Can be called multiple times to update.

**Request Body** (all fields optional):
```json
{
  "grade": "9/10",
  "observation": "Excellent work. Minor errors in the writing section."
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `grade` | string | No | Free-form grade string (e.g. `"9/10"`, `"A"`, `"Pass"`) — max 50 characters |
| `observation` | string | No | Teacher feedback text |

**Response** `200`:
```json
{
  "message": "Submission graded successfully",
  "submission": { ... }
}
```

**Errors**:
- `404` — test not found or submission not found
- `422` — submission status is not `"submitted"`

---

### Grade Individual Question Responses

`PATCH /api/teacher/tests/{testId}/submissions/{submissionId}/grade-responses`

Sets a grade, observation, and/or an attached correction file on one or more individual question responses. Can be called multiple times — only the listed responses are updated.

Accepts **`multipart/form-data`** so that correction files can be sent alongside the grading data.

**Request** (multipart/form-data):

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `responses` | JSON string | Yes | Array of `{ response_id, grade, observation }` — send as a JSON-encoded string in the form body |
| `responses[*].response_id` | integer | Yes | `response_id` from the submission's `responses` array |
| `responses[*].grade` | string | No | Per-question grade — max 50 characters |
| `responses[*].observation` | string | No | Per-question teacher comment |
| `files[{response_id}]` | file | No | Correction file for the given response ID. Max 50 MB. Stored at `teachers/{username}/private/corrections/{submissionId}_{responseId}.{ext}` |

**Example form fields:**
```
responses = [{"response_id":33,"grade":"3/3","observation":"Excellent."},{"response_id":34,"grade":"1/3","observation":"See attached."}]
files[34]  = <uploaded correction file>
```

**Response** `200`:
```json
{
  "message": "Responses graded successfully",
  "submission": { ... }
}
```

**Errors**:
- `404` — test, submission, or a response ID not found in this submission
- `422` — submission not yet submitted, or validation failed

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
