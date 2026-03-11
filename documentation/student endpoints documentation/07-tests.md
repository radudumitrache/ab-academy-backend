# Tests

Tests work identically to homework from the student's perspective — view questions, save answers as a draft, then submit.

---

## Submission Flow

```
GET  /api/student/tests                   → list all assigned tests
GET  /api/student/tests/{id}              → view test with questions
GET  /api/student/tests/{id}/results      → view submission results and teacher feedback
POST /api/student/tests/{id}/answers      → save answers (can repeat)
POST /api/student/tests/{id}/submit       → finalize submission
```

---

## List Assigned Tests

`GET /api/student/tests`

Returns all tests assigned to this student (directly or via group), ordered by due date descending. **Includes past tests** — no date filter is applied.

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

## Get Submission Results and Feedback

`GET /api/student/tests/{id}/results`

Returns the student's submitted answers alongside the teacher's grade and feedback for each response. Only available after the test has been submitted.

**Response** `200`:
```json
{
  "message": "Results retrieved successfully",
  "results": {
    "submission_id": 4,
    "submitted_at": "2026-03-30T14:00:00.000000Z",
    "grade": "9/10",
    "observation": "Excellent work. Minor errors in the writing section.",
    "responses": [
      {
        "response_id": 11,
        "question_id": 7,
        "question_type": "multiple_choice",
        "question_text": "Choose the correct form of the verb.",
        "answer": "1",
        "answer_text": "went",
        "correct_answer": "went",
        "grade": "1/1",
        "observation": "Correct.",
        "correction_file_url": null
      },
      {
        "response_id": 12,
        "question_id": 8,
        "question_type": "writing",
        "question_text": "Write a short essay about technology.",
        "answer": "My essay answer.",
        "answer_text": null,
        "correct_answer": null,
        "grade": "2/3",
        "observation": "Good structure, needs more examples.",
        "correction_file_url": "https://storage.googleapis.com/...?X-Goog-Signature=..."
      }
    ]
  }
}
```

Response field descriptions are identical to the homework results endpoint — see [06-homework.md](06-homework.md#get-submission-results-and-feedback) for the full field table.

**Errors**:
- `404` — test not found, not assigned, or not yet submitted
- `422` — submission exists but has not been submitted yet

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
