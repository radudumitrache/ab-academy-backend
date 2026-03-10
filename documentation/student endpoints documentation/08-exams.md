# Exams (Student)

Students can browse available exams created by the admin and self-register for upcoming ones. They can also record their own score and notes on enrolled exams.

---

## Endpoints

```
GET    /api/student/exams              → list all enrolled exams
GET    /api/student/exams/available    → list upcoming exams available to register
POST   /api/student/exams              → register for an existing exam
GET    /api/student/exams/{id}         → get a single enrolled exam
PATCH  /api/student/exams/{id}/score   → update own score / notes
DELETE /api/student/exams/{id}         → unregister from an exam
```

---

## List Enrolled Exams

`GET /api/student/exams`

Returns all exams the student is currently enrolled in, ordered by date.

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
    }
  ]
}
```

---

## List Available Exams

`GET /api/student/exams/available`

Returns all upcoming exams the student is **not yet** enrolled in. Use this to show a registration list.

**Response** `200`:
```json
{
  "message": "Available exams retrieved successfully",
  "count": 3,
  "exams": [
    {
      "id": 4,
      "name": "IELTS Academic",
      "exam_type": "IELTS",
      "date": "2026-05-10",
      "status": "upcoming"
    }
  ]
}
```

---

## Register for an Exam

`POST /api/student/exams`

Enrolls the student in an existing exam created by the admin.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `exam_id` | integer | Yes | ID of an existing exam |

**Request**:
```json
{ "exam_id": 4 }
```

**Response** `201`:
```json
{
  "message": "Successfully registered for exam",
  "exam": {
    "id": 4,
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

**Errors**:
- `404` / `422` if the exam does not exist or is not upcoming
- `409` if already registered

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
  "exam": { ... }
}
```

**Errors**: `404` if not enrolled in this exam.

---

## Unregister from an Exam

`DELETE /api/student/exams/{id}`

Removes the student from the exam. Blocked if an admin has already set a score or feedback.

**Response** `200`:
```json
{ "message": "Successfully unregistered from exam" }
```

**Errors**: `403` if the exam has been graded, `404` if not enrolled.

---

## Exam Status Values

| Status | Meaning |
|--------|---------|
| `upcoming` | Scheduled, not yet taken |
| `to_be_corrected` | Taken, awaiting correction |
| `passed` | Corrected and passed |
| `failed` | Corrected and failed |

---

## Field Reference

| Field | Set by | Description |
|-------|--------|-------------|
| `admin_score` | Admin | Score entered by the admin after correction |
| `feedback` | Admin | Textual feedback from the admin |
| `student_score` | Student | Grade the student recorded themselves |
| `notes` | Student | Personal notes about the exam |
