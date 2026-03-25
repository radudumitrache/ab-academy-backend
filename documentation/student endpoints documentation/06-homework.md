# Homework (Student)

Students can view homework assigned to them (directly or via a group), save answers at any time as a draft, and submit when done. Answers can be text or file uploads.

---

## Submission Flow

```
GET  /api/student/homework                   → list all assigned homework
GET  /api/student/homework/{id}              → view homework with questions + existing responses
GET  /api/student/homework/{id}/results      → view submission results and teacher feedback
POST /api/student/homework/{id}/answers      → save answers (text and/or files, repeatable)
POST /api/student/homework/{id}/submit       → finalize submission
```

---

## List Assigned Homework

`GET /api/student/homework`

Returns all homework assigned to this student (directly or via group), ordered by due date descending. **Includes past homework** — no date filter is applied.

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
      "submission_status": "submitted",
      "submitted_at": "2026-03-14T18:30:00.000000Z",
      "grade": "8/10",
      "observation": "Good effort, but check your grammar in section 2."
    }
  ]
}
```

`submission_status` values: `not_started`, `in_progress`, `submitted`.

`grade` and `observation` are `null` until the teacher grades the submission.

---

## Get Single Homework (with questions and responses)

`GET /api/student/homework/{id}`

Returns the full homework with all sections, questions, and the student's existing responses. Material IDs in `instruction_files` are resolved to 60-minute signed GCS URLs. File responses include a signed download URL.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "homework": {
    "id": 1,
    "homework_title": "Unit 5 Practice",
    "due_date": "2026-03-15",
    "submission_status": "submitted",
    "submitted_at": "2026-03-14T18:30:00.000000Z",
    "grade": "8/10",
    "observation": "Good effort, but check your grammar in section 2.",
    "responses": [
      {
        "question_id": 5,
        "answer": "Steam power",
        "file_path": null,
        "file_url": null,
        "grade": "2/2",
        "observation": "See attached correction.",
        "correction_file_url": "https://storage.googleapis.com/...?X-Goog-Signature=..."
      },
      {
        "question_id": 6,
        "answer": null,
        "file_path": "teachers/teacherTest/private/submissions/12_unit-5-practice_writing_1.pdf",
        "file_url": "https://storage.googleapis.com/...?X-Goog-Signature=...",
        "grade": "1/2",
        "observation": "Good structure.",
        "correction_file_url": null
      }
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
        "questions": [ { "..." : "..." } ]
      }
    ]
  }
}
```

**Errors**: `404` if not assigned to this student.

---

## Get Submission Results and Feedback

`GET /api/student/homework/{id}/results`

Returns the student's submitted answers alongside the teacher's grade and feedback for each response. Only available after the homework has been submitted.

**Response** `200`:
```json
{
  "message": "Results retrieved successfully",
  "results": {
    "submission_id": 3,
    "submitted_at": "2026-03-14T18:30:00.000000Z",
    "grade": "8/10",
    "observation": "Good effort, but check your grammar in section 2.",
    "responses": [
      {
        "response_id": 7,
        "question_id": 5,
        "question_type": "multiple_choice",
        "question_text": "Which invention started the Industrial Revolution?",
        "answer": "1",
        "answer_text": "Steam engine",
        "correct_answer": "Steam engine",
        "grade": "2/2",
        "observation": "Correct.",
        "correction_file_url": null
      },
      {
        "response_id": 8,
        "question_id": 6,
        "question_type": "gap_fill",
        "question_text": "Fill in the blanks.",
        "answer": "{\"0\":\"steam\",\"1\":\"coal\"}",
        "answer_text": null,
        "correct_answer": {"0": "steam", "1": "coal"},
        "grade": "1/2",
        "observation": "Second blank is incorrect.",
        "correction_file_url": "https://storage.googleapis.com/...?X-Goog-Signature=..."
      },
      {
        "response_id": 9,
        "question_id": 7,
        "question_type": "writing",
        "question_text": "Write a short essay.",
        "answer": null,
        "answer_text": null,
        "correct_answer": null,
        "grade": "5/6",
        "observation": "See attached correction.",
        "correction_file_url": "https://storage.googleapis.com/...?X-Goog-Signature=..."
      }
    ]
  }
}
```

**Response fields per item in `responses`:**

| Field | Type | Notes |
|-------|------|-------|
| `response_id` | integer | |
| `question_id` | integer | |
| `question_type` | string | e.g. `multiple_choice`, `gap_fill`, `writing` |
| `question_text` | string | The question prompt |
| `answer` | string\|null | The raw stored answer (index for multiple choice, JSON for structured types, text otherwise) |
| `answer_text` | string\|null | For `multiple_choice` only: the variant text resolved from the stored index |
| `correct_answer` | mixed\|null | Correct answer from question definition (see table below). `null` for open-ended types |
| `grade` | string\|null | Teacher-assigned grade per response (e.g. `"2/2"`). `null` until graded |
| `observation` | string\|null | Teacher observation for this response. `null` until graded |
| `correction_file_url` | string\|null | 60-min signed GCS URL for a correction file the teacher uploaded, if any |

**`correct_answer` by question type:**

| Question type | `correct_answer` value |
|---------------|------------------------|
| `multiple_choice` | Variant text string |
| `gap_fill` | Object/array of correct answers |
| `text_completion` | Object/array of correct answers |
| `correlation` | Object/array of correct pairs |
| `correct` | Sample answer string |
| `word_formation` | Sample answer string |
| `rephrase` | Sample answer string |
| `replace` | Sample answer string |
| `word_derivation` | Sample answer string |
| `reading`, `writing`, `speaking` | `null` |

**Errors**:
- `404` — homework not found, not assigned, or not yet submitted
- `422` — submission exists but has not been submitted yet

---

## Save Answers (Draft or Edit After Submission)

`POST /api/student/homework/{id}/answers`

Saves answers without submitting. Can be called multiple times — each call upserts the provided answers. Answers for questions not included in the request are preserved.

**Editing after submission is allowed** as long as the teacher has not yet graded any individual response. If the submission is already `submitted` and no response has a grade, answers are updated and `submitted_at` is refreshed to the current time. Once any response receives a grade, the submission is locked and further edits return `409`.

Accepts **`multipart/form-data`** to support both text and file answers in the same request.

### Text answers

Send `answers` as a JSON array in the form body:

```
answers[0][question_id] = 5
answers[0][answer]      = Steam power
answers[1][question_id] = 7
answers[1][answer]      = My essay answer here.
```

### File answers

Send files keyed by the question ID:

```
files[6] = <uploaded file>
files[8] = <uploaded file>
```

**File storage path:**
```
teachers/{teacher_username}/private/submissions/{student_id}_{homework-slug}_{section-slug}_{question-index}.{ext}
```

The `submissions` folder is created automatically if it does not exist.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `answers` | array | No* | Array of `{question_id, answer}` pairs for text answers |
| `answers.*.question_id` | integer | Yes (if answers sent) | Must belong to this homework |
| `answers.*.answer` | string | Yes (if answers sent) | The student's text answer |
| `files[{question_id}]` | file | No* | File upload keyed by question ID. Max 50 MB per file |

\* At least one of `answers` or `files` must be provided.

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
      { "response_id": 7, "related_question": 5, "answer": "Steam power", "file_path": null },
      { "response_id": 8, "related_question": 6, "answer": null, "file_path": "teachers/teacherTest/private/submissions/12_unit-5-practice_writing_1.pdf" }
    ]
  }
}
```

**Errors**:
- `404` — homework not found or not assigned
- `409` — submission is locked because grading has already started (`"Cannot edit answers after grading has started"`)
- `422` — neither answers nor files provided

---

## Submit Homework

`POST /api/student/homework/{id}/submit`

Finalizes the submission. Students may resubmit as many times as needed until the teacher assigns a grade. Once a grade has been set on the submission, resubmission is blocked. After submitting, answers can still be edited via `POST /answers` until the teacher grades at least one individual response.

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
- `409` — submission has already been graded (`"Homework already submitted and graded"`)
