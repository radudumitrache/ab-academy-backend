# Homework

Students can view homework assigned to them (directly or via a group), save answers at any time as a draft, and then submit when done.

---

## Submission Flow

```
GET  /api/student/homework           → list all assigned homework
GET  /api/student/homework/{id}      → view homework with questions
POST /api/student/homework/{id}/answers  → save answers (can repeat)
POST /api/student/homework/{id}/submit   → finalize submission
```

---

## List Assigned Homework

`GET /api/student/homework`

Returns all homework assigned to this student (directly or via group), ordered by due date descending.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "count": 2,
  "homework": [
    {
      "id": 1,
      "homework_title": "Unit 5 Practice",
      "homework_description": "Complete all sections before the due date.",
      "due_date": "2026-03-15",
      "submission_status": "not_started",
      "submitted_at": null
    }
  ]
}
```

`submission_status` values: `not_started`, `in_progress`, `submitted`.

---

## Get Single Homework (with questions)

`GET /api/student/homework/{id}`

Returns the homework with all sections and questions eagerly loaded. Material IDs in `instruction_files` are resolved to 60-minute signed GCS URLs.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "homework": {
    "id": 1,
    "homework_title": "Unit 5 Practice",
    "due_date": "2026-03-15",
    "submission_status": "in_progress",
    "submitted_at": null,
    "responses": [
      { "question_id": 5, "answer": "went" }
    ],
    "sections": [
      {
        "id": 2,
        "section_type": "Reading",
        "title": "Passage A",
        "instruction_text": "Read the passage carefully before answering.",
        "instruction_files": [5],
        "instruction_file_urls": [
          { "material_id": 5, "url": "https://storage.googleapis.com/...?X-Goog-Signature=..." }
        ],
        "passage": "The industrial revolution began in Britain...",
        "audio_url": null,
        "audio_material_id": null,
        "order": 1,
        "questions": [
          {
            "question_id": 5,
            "question_type": "reading_multiple_choice",
            "question_text": "What triggered the industrial revolution?",
            "multiple_choice_details": {
              "variants": ["Steam power", "Electricity", "Wind power"],
              "correct_variant": 0
            },
            "instruction_files": [],
            "instruction_file_urls": []
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

`POST /api/student/homework/{id}/answers`

Saves answers without submitting. Can be called multiple times — each call upserts the answers for the provided question IDs. Answers for questions not included are preserved.

```json
{
  "answers": [
    { "question_id": 5, "answer": "Steam power" },
    { "question_id": 6, "answer": "My essay answer here." }
  ]
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `answers` | array | Yes | Array of question/answer pairs |
| `answers.*.question_id` | integer | Yes | Must belong to this homework |
| `answers.*.answer` | string | Yes | The student's answer |

**Response** `200`:
```json
{
  "message": "Answers saved successfully",
  "submission": {
    "id": 3,
    "homework_id": 1,
    "student_id": 12,
    "status": "in_progress",
    "submitted_at": null,
    "responses": [
      { "response_id": 7, "related_question": 5, "answer": "Steam power" }
    ]
  }
}
```

**Errors**:
- `404` — homework not found or not assigned
- `409` — homework already submitted

---

## Submit Homework

`POST /api/student/homework/{id}/submit`

Finalizes the submission. After this, answers can no longer be changed.

No request body required.

**Response** `200`:
```json
{
  "message": "Homework submitted successfully",
  "submission": {
    "id": 3,
    "homework_id": 1,
    "student_id": 12,
    "status": "submitted",
    "submitted_at": "2026-03-14T18:30:00.000000Z"
  }
}
```

**Errors**:
- `404` — homework not found or not assigned
- `409` — already submitted
- `422` — no answers saved yet
