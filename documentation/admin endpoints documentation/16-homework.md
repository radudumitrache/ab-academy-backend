# Homework (Admin)

Admins have full access to all homework across all teachers — no ownership restriction. The API mirrors the teacher homework API with two key differences:

1. `GET /api/admin/homework` returns **all** homework, not just one teacher's.
2. `POST /api/admin/homework` accepts an optional `homework_teacher` field to set the owner; defaults to the admin's own ID.
3. Assign accepts **any** student or group, not only those belonging to a specific teacher.
4. A `GET /{id}/submissions` endpoint allows the admin to view all student submissions.

---

## Creation Flow

```
POST /api/admin/homework                         → create homework
POST /api/admin/homework/{id}/assign             → assign students/groups
POST /api/admin/homework/{id}/sections           → add sections
POST /api/admin/homework/{id}/questions          → add questions to a section
GET  /api/admin/homework/{id}/submissions        → view all submissions
```

---

## Section Types & Question Types

Identical to teacher homework — see teacher [08-homework.md](../teacher%20endpoints%20documentation/08-homework.md) for the full table.

| `section_type` | Allowed question types |
|----------------|----------------------|
| `GrammarAndVocabulary` | `multiple_choice`, `gap_fill`, `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `text_completion`, `correlation` |
| `Writing` | `rephrase`, `word_formation`, `replace`, `correct`, `word_derivation`, `writing_question` |
| `Reading` | `reading_multiple_choice`, `reading_question`, `gap_fill`, `text_completion`, `correlation` |
| `Listening` | `listening_multiple_choice`, `text_completion`, `gap_fill` |
| `Speaking` | `speaking_question` |

---

## Homework Endpoints

### List All Homework

`GET /api/admin/homework`

Returns all homework across all teachers, newest first. Includes `teacher` object and `all_questions_count`.

**Response** `200`:
```json
{
  "message": "Homework retrieved successfully",
  "count": 5,
  "homework": [
    {
      "id": 1,
      "homework_title": "Unit 5 Practice",
      "due_date": "2026-03-15",
      "status": "posted",
      "all_questions_count": 8,
      "teacher": { "id": 4, "username": "teacher_ana" }
    }
  ]
}
```

---

### Get Single Homework (with sections and questions)

`GET /api/admin/homework/{id}`

Returns the homework with all sections, questions, detail records, and signed GCS URLs.

---

### Create Homework

`POST /api/admin/homework`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `homework_title` | string | Yes | Max 255 characters |
| `homework_description` | string | No | |
| `due_date` | string | Yes | `YYYY-MM-DD` |
| `homework_teacher` | integer | No | User ID of the teacher; defaults to admin's ID |
| `status` | string | No | `draft` or `posted`; defaults to `draft` |
| `people_assigned` | array | No | Array of user IDs |
| `groups_assigned` | array | No | Array of group IDs |

**Response** `201` with created homework object.

---

### Update Homework

`PUT /api/admin/homework/{id}` — all fields optional (same as create minus `homework_teacher`). To publish a draft, send `"status": "posted"`. To revert to draft, send `"status": "draft"`.

---

### Delete Homework

`DELETE /api/admin/homework/{id}` — sections and questions cascade-delete.

---

### Assign Students

`POST /api/admin/homework/{id}/assign` — overwrites current assignment. No group ownership check.

```json
{
  "people_assigned": [12, 15],
  "groups_assigned": [3]
}
```

---

### View Submissions

`GET /api/admin/homework/{id}/submissions`

Returns all student submissions for this homework, including responses.

**Response** `200`:
```json
{
  "message": "Submissions retrieved successfully",
  "count": 3,
  "submissions": [
    {
      "id": 5,
      "homework_id": 1,
      "student_id": 12,
      "status": "submitted",
      "submitted_at": "2026-03-14T18:30:00.000000Z",
      "student": { "id": 12, "username": "john_doe", "email": "john@example.com" },
      "responses": [
        { "response_id": 7, "related_question": 5, "answer": "Steam power" }
      ]
    }
  ]
}
```

---

## Homework Status

| Status | Visible to students | Description |
|--------|---------------------|-------------|
| `draft` | No | Default on creation. Build and review before publishing. |
| `posted` | Yes | Students can see and submit. Can be reverted to `draft`. |

Status is changed via the `status` field in `POST` or `PUT` — there is no dedicated status-change endpoint.

---

## Section Endpoints

### List Sections

`GET /api/admin/homework/{homeworkId}/sections`

### Create Section

`POST /api/admin/homework/{homeworkId}/sections`

Same fields as teacher section creation. See teacher docs for field reference.

### Create Section + Questions in One Request (Batch)

`POST /api/admin/homework/{homeworkId}/sections/batch`

Same as teacher batch endpoint — creates a section and all its questions atomically. See teacher [08-homework.md](../teacher%20endpoints%20documentation/08-homework.md) for full field reference and example.

### Update Section

`PUT /api/admin/homework/{homeworkId}/sections/{sectionId}` — all fields optional, cannot change `section_type`.

### Delete Section

`DELETE /api/admin/homework/{homeworkId}/sections/{sectionId}` — questions cascade-delete.

---

## Question Endpoints

### Create Question

`POST /api/admin/homework/{homeworkId}/questions`

Same fields and rules as teacher question creation. `section_id` is required.

### Update Question

`PUT /api/admin/homework/{homeworkId}/questions/{questionId}`

### Delete Question

`DELETE /api/admin/homework/{homeworkId}/questions/{questionId}`
