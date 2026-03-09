# Tests

Tests work identically to homework from the student's perspective — view questions, save answers as a draft, then submit.

---

## Submission Flow

```
GET  /api/student/tests           → list all assigned tests
GET  /api/student/tests/{id}      → view test with questions
POST /api/student/tests/{id}/answers  → save answers (can repeat)
POST /api/student/tests/{id}/submit   → finalize submission
```

---

## List Assigned Tests

`GET /api/student/tests`

Returns all tests assigned to this student (directly or via group), ordered by due date descending.

**Response** `200`:
```json
{
  "message": "Tests retrieved successfully",
  "count": 1,
  "tests": [
    {
      "id": 2,
      "test_title": "Unit 5 Final Test",
      "test_description": "Covers grammar and reading comprehension.",
      "due_date": "2026-04-01",
      "submission_status": "submitted",
      "submitted_at": "2026-03-30T14:00:00.000000Z",
      "grade": "9/10",
      "observation": "Excellent work. Minor errors in the writing section."
    }
  ]
}
```

`submission_status` values: `not_started`, `in_progress`, `submitted`.

`grade` and `observation` are `null` until the teacher grades the submission.

---

## Get Single Test (with questions)

`GET /api/student/tests/{id}`

Returns the test with all sections and questions eagerly loaded. Material IDs resolved to signed URLs.

**Response** `200`:
```json
{
  "message": "Test retrieved successfully",
  "test": {
    "id": 2,
    "test_title": "Unit 5 Final Test",
    "due_date": "2026-04-01",
    "submission_status": "submitted",
    "submitted_at": "2026-03-30T14:00:00.000000Z",
    "grade": "9/10",
    "observation": "Excellent work. Minor errors in the writing section.",
    "responses": [
      {
        "question_id": 7,
        "answer": "went",
        "grade": "1/1",
        "observation": "Correct.",
        "correction_file_url": null
      },
      {
        "question_id": 8,
        "answer": "My essay answer.",
        "grade": "2/3",
        "observation": "See attached correction for improvements.",
        "correction_file_url": "https://storage.googleapis.com/...?X-Goog-Signature=..."
      }
    ],
    "sections": [
      {
        "id": 3,
        "section_type": "GrammarAndVocabulary",
        "title": "Part 1",
        "instruction_text": null,
        "instruction_files": [],
        "instruction_file_urls": [],
        "order": 1,
        "questions": [
          {
            "test_question_id": 7,
            "question_type": "multiple_choice",
            "question_text": "Choose the correct form of the verb.",
            "multiple_choice_details": {
              "variants": ["go", "went", "gone"],
              "correct_variant": 1
            }
          }
        ]
      }
    ]
  }
}
```

**Errors**: `404` if not assigned to this student.

---

## Save Answers (Draft)

`POST /api/student/tests/{id}/answers`

```json
{
  "answers": [
    { "question_id": 7, "answer": "went" }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `answers` | array | Yes | |
| `answers.*.question_id` | integer | Yes | Must be a `test_question_id` on this test |
| `answers.*.answer` | string | Yes | |

**Response** `200` with submission and responses.

**Errors**:
- `404` — test not found or not assigned
- `409` — test already submitted

---

## Submit Test

`POST /api/student/tests/{id}/submit`

No request body required. Marks status as `submitted`.

**Response** `200`:
```json
{
  "message": "Test submitted successfully",
  "submission": {
    "id": 4,
    "test_id": 2,
    "student_id": 12,
    "status": "submitted",
    "submitted_at": "2026-03-30T14:00:00.000000Z"
  }
}
```

**Errors**:
- `404` — test not found or not assigned
- `409` — already submitted
- `422` — no answers saved yet
