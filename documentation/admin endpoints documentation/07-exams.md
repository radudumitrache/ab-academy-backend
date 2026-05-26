# Exams

This section covers the API endpoints for managing exams in the AB Academy platform.

Valid status values: `upcoming`, `to_be_corrected`, `passed`, `failed`

The `exam_type` field is a free-text string (e.g. `"Ielts certificate"`, `"All sections"`, `"oral"`, `"written"`). It can be set by admin when creating or updating an exam.

## Pivot Fields

Every student object nested inside an exam carries a `pivot` block with the following fields:

| Field | Set by | Type | Description |
|-------|--------|------|-------------|
| `exam_id` | system | integer | FK to the exam |
| `student_id` | system | integer | FK to the student |
| `score` | admin | decimal \| null | Numeric grade assigned via Grade Student endpoint |
| `feedback` | admin | string \| null | Text feedback assigned via Grade Student endpoint |
| `student_score` | student | string \| null | Free-text result the student self-reported |
| `notes` | student | string \| null | Student's personal notes for this exam |
| `created_at` | system | datetime | When the student was enrolled |
| `updated_at` | system | datetime | Last time any pivot field was changed |

---

## List All Exams

- **URL**: `/api/admin/exams`
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
        "id": 1,
        "name": "IELTS SESSION Summer",
        "exam_type": "Ielts certificate",
        "date": "2026-06-05",
        "status": "upcoming",
        "created_at": "2026-05-20T22:50:07.000000Z",
        "updated_at": "2026-05-20T22:50:07.000000Z",
        "students": [
          {
            "id": 5,
            "username": "student1",
            "role": "student",
            "pivot": {
              "exam_id": 1,
              "student_id": 5,
              "score": null,
              "feedback": null,
              "student_score": null,
              "notes": null,
              "created_at": "2026-05-22T11:04:11.000000Z",
              "updated_at": "2026-05-22T11:04:11.000000Z"
            }
          }
        ]
      }
    ]
  }
  ```

---

## Get Exam Details

- **URL**: `/api/admin/exams/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  {
    "message": "Exam retrieved successfully",
    "exam": {
      "id": 1,
      "name": "IELTS SESSION Summer",
      "exam_type": "Ielts certificate",
      "date": "2026-06-05",
      "status": "to_be_corrected",
      "created_at": "2026-05-20T22:50:07.000000Z",
      "updated_at": "2026-05-20T22:50:07.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 5,
            "score": 8.5,
            "feedback": "Good work overall",
            "student_score": "8.5/10",
            "notes": "felt prepared",
            "created_at": "2026-05-22T11:04:11.000000Z",
            "updated_at": "2026-05-26T20:35:07.000000Z"
          }
        }
      ],
      "status_history": [
        {
          "id": 1,
          "exam_id": 1,
          "old_status": null,
          "new_status": "upcoming",
          "changed_by_user_id": 1,
          "created_at": "2026-05-20T22:50:07.000000Z"
        },
        {
          "id": 2,
          "exam_id": 1,
          "old_status": "upcoming",
          "new_status": "to_be_corrected",
          "changed_by_user_id": 1,
          "created_at": "2026-06-05T10:00:00.000000Z"
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```

---

## Create Exam

- **URL**: `/api/admin/exams`
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
    "name": "IELTS SESSION Summer",
    "exam_type": "Ielts certificate",
    "date": "2026-06-05",
    "status": "upcoming",
    "student_ids": [5, 6, 7]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `name` | string | Yes | Max 255 characters |
  | `exam_type` | string | No | Free-text, e.g. `"Ielts certificate"`, `"All sections"` |
  | `date` | string | Yes | `YYYY-MM-DD` format |
  | `status` | string | No | Defaults to `upcoming` |
  | `student_ids` | array | No | IDs of students to enroll immediately |

- **Success Response** `201`:
  ```json
  {
    "message": "Exam created successfully",
    "exam": {
      "id": 3,
      "name": "IELTS SESSION Summer",
      "exam_type": "Ielts certificate",
      "date": "2026-06-05",
      "status": "upcoming",
      "created_at": "2026-05-20T22:50:07.000000Z",
      "updated_at": "2026-05-20T22:50:07.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 3,
            "student_id": 5,
            "score": null,
            "feedback": null,
            "student_score": null,
            "notes": null,
            "created_at": "2026-05-20T22:50:07.000000Z",
            "updated_at": "2026-05-20T22:50:07.000000Z"
          }
        }
      ],
      "status_history": [
        {
          "id": 1,
          "exam_id": 3,
          "old_status": null,
          "new_status": "upcoming",
          "changed_by_user_id": 1,
          "created_at": "2026-05-20T22:50:07.000000Z"
        }
      ]
    }
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

---

## Update Exam

All fields are optional; only send what needs to change. Status changes are automatically recorded in `status_history`.

- **URL**: `/api/admin/exams/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "name": "IELTS SESSION Summer — Updated",
    "exam_type": "All sections",
    "date": "2026-06-10",
    "status": "to_be_corrected"
  }
  ```
- **Field Notes**:

  | Field | Type | Notes |
  |-------|------|-------|
  | `name` | string | Max 255 characters |
  | `exam_type` | string | Free-text |
  | `date` | string | `YYYY-MM-DD` format |
  | `status` | string | `upcoming`, `to_be_corrected`, `passed`, `failed` |

- **Success Response** `200`:
  ```json
  {
    "message": "Exam updated successfully",
    "exam": {
      "id": 1,
      "name": "IELTS SESSION Summer — Updated",
      "exam_type": "All sections",
      "date": "2026-06-10",
      "status": "to_be_corrected",
      "created_at": "2026-05-20T22:50:07.000000Z",
      "updated_at": "2026-05-27T08:00:00.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 5,
            "score": null,
            "feedback": null,
            "student_score": null,
            "notes": null,
            "created_at": "2026-05-22T11:04:11.000000Z",
            "updated_at": "2026-05-22T11:04:11.000000Z"
          }
        }
      ],
      "status_history": [
        {
          "id": 1,
          "exam_id": 1,
          "old_status": null,
          "new_status": "upcoming",
          "changed_by_user_id": 1,
          "created_at": "2026-05-20T22:50:07.000000Z"
        },
        {
          "id": 2,
          "exam_id": 1,
          "old_status": "upcoming",
          "new_status": "to_be_corrected",
          "changed_by_user_id": 1,
          "created_at": "2026-05-27T08:00:00.000000Z"
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "status": ["The selected status is invalid."]
      }
    }
    ```

---

## Delete Exam

- **URL**: `/api/admin/exams/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  { "message": "Exam deleted successfully" }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```

---

## Enroll Students in Exam

Adds one or more students to an existing exam. Already-enrolled students are silently skipped (no duplicates).

- **URL**: `/api/admin/exams/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "student_ids": [5, 6, 8] }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `student_ids` | array | Yes | Must all be valid student user IDs |

- **Success Response** `200`:
  ```json
  {
    "message": "Students enrolled in exam successfully",
    "exam": {
      "id": 1,
      "name": "IELTS SESSION Summer",
      "exam_type": "Ielts certificate",
      "date": "2026-06-05",
      "status": "upcoming",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 5,
            "score": null,
            "feedback": null,
            "student_score": null,
            "notes": null,
            "created_at": "2026-05-22T11:04:11.000000Z",
            "updated_at": "2026-05-22T11:04:11.000000Z"
          }
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```
  - **422** — one or more IDs are not students:
    ```json
    { "message": "All student_ids must belong to students" }
    ```

---

## Grade Student

Sets the admin-assigned score and feedback for a specific student on an exam. Both fields are optional — send only what needs to be updated.

- **URL**: `/api/admin/exams/{examId}/students/{studentId}/grade`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "score": 8.5,
    "feedback": "Good work overall, needs improvement in section 3"
  }
  ```
- **Field Notes**:

  | Field | Type | Notes |
  |-------|------|-------|
  | `score` | decimal \| null | Numeric grade |
  | `feedback` | string \| null | Text feedback visible to the student |

- **Success Response** `200`:
  ```json
  {
    "message": "Student graded successfully",
    "exam": {
      "id": 1,
      "name": "IELTS SESSION Summer",
      "exam_type": "Ielts certificate",
      "date": "2026-06-05",
      "status": "to_be_corrected",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 5,
            "score": 8.5,
            "feedback": "Good work overall, needs improvement in section 3",
            "student_score": "8.5/10",
            "notes": "felt prepared",
            "created_at": "2026-05-22T11:04:11.000000Z",
            "updated_at": "2026-05-27T09:00:00.000000Z"
          }
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```
  - **404**:
    ```json
    { "message": "Student is not enrolled in this exam" }
    ```

---

## Remove Student from Exam

- **URL**: `/api/admin/exams/{examId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  {
    "message": "Student removed from exam successfully",
    "exam": {
      "id": 1,
      "name": "IELTS SESSION Summer",
      "exam_type": "Ielts certificate",
      "date": "2026-06-05",
      "status": "upcoming",
      "students": [
        {
          "id": 6,
          "username": "student2",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 6,
            "score": null,
            "feedback": null,
            "student_score": null,
            "notes": null,
            "created_at": "2026-05-22T11:04:11.000000Z",
            "updated_at": "2026-05-22T11:04:11.000000Z"
          }
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - **404**:
    ```json
    { "message": "Exam not found" }
    ```
  - **404**:
    ```json
    { "message": "Student not found" }
    ```
  - **404**:
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
