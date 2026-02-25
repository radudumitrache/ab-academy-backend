# Exam Management

Teachers can view exams that students in their groups are enrolled in, create new exams, and manage student enrollment in those exams.

All enrollment operations are scoped to the teacher's own groups — a teacher cannot enroll students who do not belong to any of their groups.

---

## Exam Object

```json
{
  "id": 7,
  "name": "English B1 — March 2026",
  "date": "2026-03-15",
  "status": "upcoming",
  "students": [
    {
      "id": 12,
      "username": "student1",
      "role": "student",
      "pivot": {
        "exam_id": 7,
        "student_id": 12,
        "score": null,
        "feedback": null
      }
    }
  ],
  "created_at": "2026-02-20T10:00:00.000000Z",
  "updated_at": "2026-02-20T10:00:00.000000Z"
}
```

| Field | Description |
|-------|-------------|
| `id` | Unique exam identifier |
| `name` | Name or title of the exam |
| `date` | Exam date (`YYYY-MM-DD`) |
| `status` | One of `upcoming`, `to_be_corrected`, `passed`, `failed` |
| `students` | Students currently enrolled, with their `score` and `feedback` from the pivot |

---

## List All Exams

Returns all exams in the system. Teachers need to see all exams to be able to enroll their students in them.

- **URL**: `/api/teacher/exams`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Exams retrieved successfully",
    "count": 2,
    "exams": [
      {
        "id": 7,
        "name": "English B1 — March 2026",
        "date": "2026-03-15",
        "status": "upcoming",
        "students": [ ... ]
      }
    ]
  }
  ```

- **Notes**:
  - Returns all exams regardless of student enrollment. Use the enroll endpoints to add your students to specific exams.

---

## Get Single Exam

Returns a single exam with full student enrollment and status history.
Only accessible if at least one of the teacher's students is enrolled in it.

- **URL**: `/api/teacher/exams/{id}`
- **Method**: `GET`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Exam retrieved successfully",
    "exam": {
      "id": 7,
      "name": "English B1 — March 2026",
      "date": "2026-03-15",
      "status": "upcoming",
      "students": [ ... ],
      "status_history": [ ... ]
    }
  }
  ```

- **Error Responses**:
  - **404** — exam not found:
    ```json
    { "message": "Exam not found" }
    ```
  - **403** — none of the teacher's students are enrolled:
    ```json
    { "message": "Unauthorized" }
    ```

---

## Create Exam

Creates a new exam. Optionally enroll students from the teacher's groups at creation time.

- **URL**: `/api/teacher/exams`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "name": "English B1 — March 2026",
    "date": "2026-03-15",
    "status": "upcoming",
    "student_ids": [12, 15, 19]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `name` | string | Yes | Max 255 characters |
  | `date` | string | Yes | `YYYY-MM-DD` format |
  | `status` | string | No | `upcoming` (default), `to_be_corrected`, `passed`, `failed` |
  | `student_ids` | array | No | IDs of students to enroll — must all belong to the teacher's groups |

- **Success Response** `201`:
  ```json
  {
    "message": "Exam created successfully",
    "exam": { ... }
  }
  ```

- **Error Responses**:
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "name": ["The name field is required."],
        "date": ["The date does not match the format Y-m-d."]
      }
    }
    ```
  - **422** — one or more `student_ids` are not students from the teacher's groups:
    ```json
    { "message": "All student_ids must be valid students belonging to your groups" }
    ```

---

## Enroll Students in an Exam

Adds one or more students to an existing exam. Students must belong to one of the teacher's groups.
Already-enrolled students are silently skipped (no error, no duplicate).

- **URL**: `/api/teacher/exams/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "student_ids": [12, 15] }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `student_ids` | array | Yes | Must all be valid students in teacher's groups |

- **Success Response** `200`:
  ```json
  {
    "message": "Students enrolled in exam successfully",
    "exam": { ... }
  }
  ```

- **Error Responses**:
  - **404** — exam not found:
    ```json
    { "message": "Exam not found" }
    ```
  - **422** — student not in teacher's groups:
    ```json
    { "message": "All student_ids must be valid students belonging to your groups" }
    ```

---

## Remove Student from Exam

Removes a single student from an exam. The student must belong to one of the teacher's groups.

- **URL**: `/api/teacher/exams/{examId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Student removed from exam successfully",
    "exam": { ... }
  }
  ```

- **Error Responses**:
  - **404** — exam not found:
    ```json
    { "message": "Exam not found" }
    ```
  - **403** — student does not belong to teacher's groups:
    ```json
    { "message": "Student does not belong to your groups" }
    ```
  - **404** — student is not enrolled in this exam:
    ```json
    { "message": "Student is not enrolled in this exam" }
    ```

---

## Exam Status Reference

| Value | Meaning |
|-------|---------|
| `upcoming` | Exam is scheduled but not yet taken (default) |
| `to_be_corrected` | Exam has been taken, results pending |
| `passed` | Student(s) passed |
| `failed` | Student(s) failed |
