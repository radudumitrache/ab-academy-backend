# Tests (Admin)

Admins have full access to all tests across all teachers — identical to the homework admin API but for tests.

Key differences from teacher tests:
1. `GET /api/admin/tests` returns all tests platform-wide.
2. `POST /api/admin/tests` accepts an optional `test_teacher` to set the owner.
3. Assign accepts any student or group — no ownership check.
4. `GET /api/admin/tests/{id}/submissions` shows all student submissions.

---

## Creation Flow

```
POST /api/admin/tests                                → create test
POST /api/admin/tests/{id}/assign                    → assign students/groups
POST /api/admin/tests/{id}/sections                  → add a section
POST /api/admin/tests/{id}/sections/batch            → add a section + all its questions in one call
POST /api/admin/tests/{id}/questions                 → add a question to an existing section
GET  /api/admin/tests/{id}/submissions               → view all submissions
```

---

## Section Types & Question Types

| `section_type` | Allowed question types |
|----------------|----------------------|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `writing_question` |
| `Reading` | `reading_multiple_choice`, `reading_question`, `gap_fill`, `text_completion`, `correlation` |
| `Listening` | `listening_multiple_choice`, `text_completion`, `gap_fill` |
| `Speaking` | `speaking_question` |
| `Mixed` | `mixed_question` |

**`mixed_question`** — open-ended question where the student submits a long text response **or** uploads a file (up to 50 MB). Supports an optional `sample_answer` field for teacher reference. Students answer via `POST /api/student/tests/{id}/answers` using `answers[].answer` (text) or `files[{question_id}]` (file upload).

---

## Test Endpoints

### List All Tests

`GET /api/admin/tests`

Returns all tests platform-wide, newest first. Includes `teacher` and `all_questions_count`.

**Response** `200`:
```json
{
  "message": "Tests retrieved successfully",
  "count": 3,
  "tests": [
    {
      "id": 2,
      "test_title": "Unit 5 Final Test",
      "due_date": "2026-04-01",
      "all_questions_count": 12,
      "teacher": { "id": 4, "username": "teacher_ana" }
    }
  ]
}
```

---

### Get Single Test (with sections and questions)

`GET /api/admin/tests/{id}`

Returns full test with sections, questions, detail records, and signed GCS URLs.

---

### Create Test

`POST /api/admin/tests`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `test_title` | string | Yes | Max 255 characters |
| `test_description` | string | No | |
| `due_date` | string | Yes | `YYYY-MM-DD` |
| `test_teacher` | integer | No | User ID of the teacher owner; defaults to admin's ID |
| `people_assigned` | array | No | Array of user IDs |
| `groups_assigned` | array | No | Array of group IDs |

**Response** `201` with created test object.

---

### Update Test

`PUT /api/admin/tests/{id}` — all fields optional (same as create minus `test_teacher`).

---

### Delete Test

`DELETE /api/admin/tests/{id}` — sections and questions cascade-delete.

---

### Assign Students

`POST /api/admin/tests/{id}/assign` — overwrites current assignment.

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

When `is_global` is `true`, the test is visible to every student on the platform — including users who register after the test is created. Omitting `is_global` from the request resets it to `false`.

---

### View Submissions

`GET /api/admin/tests/{id}/submissions`

**Response** `200`:
```json
{
  "message": "Submissions retrieved successfully",
  "count": 2,
  "submissions": [
    {
      "id": 4,
      "test_id": 2,
      "student_id": 12,
      "status": "submitted",
      "submitted_at": "2026-03-30T14:00:00.000000Z",
      "student": { "id": 12, "username": "john_doe", "email": "john@example.com" },
      "responses": [
        { "response_id": 9, "related_question": 7, "answer": "went" }
      ]
    }
  ]
}
```

---

## Section Endpoints

### List Sections
`GET /api/admin/tests/{testId}/sections`

### Create Section
`POST /api/admin/tests/{testId}/sections` — same fields as teacher section.

### Create Section + Questions in One Request (Batch)

`POST /api/admin/tests/{testId}/sections/batch`

Creates a section and all its questions in a single atomic transaction. No ownership check — admin can batch-create sections on any test. Logs the action to `DatabaseLog`.

Accepts the same section fields as **Create Section**, plus a `questions` array. Identical request/response shape to the teacher batch endpoint:

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
    }
  ]
}
```

**Response** `201`:
```json
{
  "message": "Section created successfully with questions",
  "section": {
    "id": 2,
    "section_type": "Reading",
    "questions": [ { ... } ]
  }
}
```

**Errors**:
- `404` — test not found
- `422` — invalid section type, invalid question type for section, or validation failure

### Update Section
`PUT /api/admin/tests/{testId}/sections/{sectionId}`

### Delete Section
`DELETE /api/admin/tests/{testId}/sections/{sectionId}` — questions cascade-delete.

---

## Question Endpoints

### Create Question
`POST /api/admin/tests/{testId}/questions` — same fields and rules as teacher. `section_id` required.

### Update Question
`PUT /api/admin/tests/{testId}/questions/{questionId}`

### Delete Question
`DELETE /api/admin/tests/{testId}/questions/{questionId}`
