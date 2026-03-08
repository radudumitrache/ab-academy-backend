# Exams (Student)

Two separate exam systems exist for students:

1. **Admin-enrolled exams** — formal exams the admin has enrolled the student in. Read-only from the student side.
2. **Personal exams** — exams the student registers themselves (e.g. IELTS, Cambridge, TOEFL) and tracks their own score and notes.

---

## Admin-Enrolled Exams (Read-Only)

### List Enrolled Exams

`GET /api/student/exams`

Returns all exams the student has been enrolled in by the admin, ordered by date.

**Response** `200`:
```json
{
  "message": "Exams retrieved successfully",
  "count": 2,
  "exams": [
    {
      "id": 1,
      "name": "Cambridge B2 Mock Exam",
      "date": "2026-04-15",
      "status": "upcoming",
      "score": null,
      "feedback": null
    },
    {
      "id": 2,
      "name": "Internal Grammar Test",
      "date": "2026-03-01",
      "status": "passed",
      "score": 87,
      "feedback": "Excellent work on the reading section."
    }
  ]
}
```

#### Exam Status Values

| Status | Meaning |
|--------|---------|
| `upcoming` | Scheduled, not yet taken |
| `to_be_corrected` | Taken, awaiting correction |
| `passed` | Corrected and passed |
| `failed` | Corrected and failed |

---

### Get Single Enrolled Exam

`GET /api/student/exams/{id}`

**Response** `200`:
```json
{
  "message": "Exam retrieved successfully",
  "exam": {
    "id": 1,
    "name": "Cambridge B2 Mock Exam",
    "date": "2026-04-15",
    "status": "upcoming",
    "score": null,
    "feedback": null
  }
}
```

**Errors**: `404` if not found or student is not enrolled.

---

## Personal Exams (Student-Managed)

Students can record exams they have personally registered for and update their score once results are available.

### List Personal Exams

`GET /api/student/personal-exams`

**Response** `200`:
```json
{
  "message": "Personal exams retrieved successfully",
  "count": 1,
  "exams": [
    {
      "id": 1,
      "student_id": 12,
      "name": "IELTS Academic",
      "exam_type": "IELTS",
      "date": "2026-05-10",
      "score": null,
      "notes": "Registered at the British Council centre.",
      "created_at": "2026-03-07T10:00:00.000000Z",
      "updated_at": "2026-03-07T10:00:00.000000Z"
    }
  ]
}
```

---

### Create a Personal Exam

`POST /api/student/personal-exams`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | Yes | Max 255 characters |
| `exam_type` | string | No | e.g. `IELTS`, `Cambridge B2`, `TOEFL` |
| `date` | date | No | `YYYY-MM-DD` |
| `score` | string | No | Free text: `"7.5"`, `"B2"`, `"87/100"` |
| `notes` | string | No | Any additional notes |

**Response** `201`:
```json
{
  "message": "Personal exam created successfully",
  "exam": {
    "id": 1,
    "student_id": 12,
    "name": "IELTS Academic",
    "exam_type": "IELTS",
    "date": "2026-05-10",
    "score": null,
    "notes": "Registered at the British Council centre.",
    "created_at": "2026-03-08T10:00:00.000000Z",
    "updated_at": "2026-03-08T10:00:00.000000Z"
  }
}
```

---

### Get Single Personal Exam

`GET /api/student/personal-exams/{id}`

**Response** `200` with exam object. **Errors**: `404` if not found or not owned by the student.

---

### Update a Personal Exam

`PUT /api/student/personal-exams/{id}`

All fields optional. Typically used to add a score after results are received.

```json
{ "score": "7.5", "notes": "Passed with band 7.5 overall." }
```

**Response** `200`:
```json
{
  "message": "Personal exam updated successfully",
  "exam": {
    "id": 1,
    "name": "IELTS Academic",
    "exam_type": "IELTS",
    "date": "2026-05-10",
    "score": "7.5",
    "notes": "Passed with band 7.5 overall."
  }
}
```

**Errors**: `404` if not found or not owned by the student.

---

### Delete a Personal Exam

`DELETE /api/student/personal-exams/{id}`

**Response** `200`:
```json
{ "message": "Personal exam deleted successfully" }
```

**Errors**: `404` if not found or not owned by the student.
