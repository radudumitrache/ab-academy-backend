# Exams (Student)

Students have a single unified exam list. Exams can be added by the admin (with the student enrolled) or created by the student themselves. In both cases the student can record their own score and notes.

---

## Endpoints

```
GET    /api/student/exams              → list all enrolled exams
POST   /api/student/exams              → create a new exam and self-enroll
GET    /api/student/exams/{id}         → get a single exam
PATCH  /api/student/exams/{id}/score   → update own score / notes
DELETE /api/student/exams/{id}         → delete a self-created exam
```

---

## List Exams

`GET /api/student/exams`

Returns all exams the student is enrolled in, ordered by date.

**Response** `200`:
```json
{
  "message": "Exams retrieved successfully",
  "count": 2,
  "exams": [
    {
      "id": 1,
      "name": "Cambridge B2 Mock Exam",
      "exam_type": "Cambridge",
      "date": "2026-04-15",
      "status": "upcoming",
      "admin_score": null,
      "feedback": null,
      "student_score": null,
      "notes": null
    },
    {
      "id": 3,
      "name": "IELTS Academic",
      "exam_type": "IELTS",
      "date": "2026-05-10",
      "status": "upcoming",
      "admin_score": null,
      "feedback": null,
      "student_score": "7.5",
      "notes": "Passed with band 7.5 overall."
    }
  ]
}
```

### Exam Status Values

| Status | Meaning |
|--------|---------|
| `upcoming` | Scheduled, not yet taken |
| `to_be_corrected` | Taken, awaiting correction |
| `passed` | Corrected and passed |
| `failed` | Corrected and failed |

---

## Create an Exam

`POST /api/student/exams`

Students register an exam they have signed up for. The student is automatically enrolled.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | Yes | Max 255 characters |
| `exam_type` | string | No | e.g. `IELTS`, `Cambridge B2`, `TOEFL` |
| `date` | date | No | `YYYY-MM-DD` |

**Response** `201`:
```json
{
  "message": "Exam created successfully",
  "exam": {
    "id": 3,
    "name": "IELTS Academic",
    "exam_type": "IELTS",
    "date": "2026-05-10",
    "status": "upcoming",
    "admin_score": null,
    "feedback": null,
    "student_score": null,
    "notes": null
  }
}
```

---

## Get Single Exam

`GET /api/student/exams/{id}`

**Response** `200` with the exam object above.

**Errors**: `404` if not found or student is not enrolled.

---

## Update Score / Notes

`PATCH /api/student/exams/{id}/score`

Students record their own grade after taking the exam. Does not affect the admin-set `admin_score` or `feedback`.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `student_score` | string | No | Free text — `"7.5"`, `"B2"`, `"87/100"` |
| `notes` | string | No | Personal notes about the result |

```json
{ "student_score": "7.5", "notes": "Passed with band 7.5 overall." }
```

**Response** `200`:
```json
{
  "message": "Score updated successfully",
  "exam": {
    "id": 3,
    "name": "IELTS Academic",
    "exam_type": "IELTS",
    "date": "2026-05-10",
    "status": "upcoming",
    "admin_score": null,
    "feedback": null,
    "student_score": "7.5",
    "notes": "Passed with band 7.5 overall."
  }
}
```

**Errors**: `404` if not enrolled in this exam.

---

## Delete an Exam

`DELETE /api/student/exams/{id}`

Students can only delete exams they created themselves. Blocked if an admin has already set a score or feedback.

**Response** `200`:
```json
{ "message": "Exam deleted successfully" }
```

**Errors**: `403` if the exam has been graded by an admin, `404` if not enrolled.

---

## Field Reference

| Field | Set by | Description |
|-------|--------|-------------|
| `admin_score` | Admin | Score entered by the admin after correction |
| `feedback` | Admin | Textual feedback from the admin |
| `student_score` | Student | Grade the student recorded themselves |
| `notes` | Student | Personal notes about the exam |
