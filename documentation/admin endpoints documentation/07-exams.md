# Exams

This section covers the API endpoints for managing exams in the AB Academy platform.

Valid status values: `upcoming`, `to_be_corrected`, `passed`, `failed`

## List All Exams

- **URL**: `/api/admin/exams`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Exams retrieved successfully",
    "count": 2,
    "exams": [
      {
        "id": 1,
        "name": "Mathematics Midterm",
        "date": "2026-03-15",
        "status": "upcoming",
        "created_at": "2026-02-01T10:00:00.000000Z",
        "updated_at": "2026-02-01T10:00:00.000000Z",
        "students": [
          {
            "id": 5,
            "username": "student1",
            "role": "student",
            "pivot": {
              "exam_id": 1,
              "student_id": 5
            }
          },
          {
            "id": 6,
            "username": "student2",
            "role": "student",
            "pivot": {
              "exam_id": 1,
              "student_id": 6
            }
          }
        ]
      },
      {
        "id": 2,
        "name": "Physics Final",
        "date": "2026-03-20",
        "status": "upcoming",
        "created_at": "2026-02-02T11:00:00.000000Z",
        "updated_at": "2026-02-02T11:00:00.000000Z",
        "students": [
          {
            "id": 7,
            "username": "student3",
            "role": "student",
            "pivot": {
              "exam_id": 2,
              "student_id": 7
            }
          }
        ]
      }
    ]
  }
  ```

## Create Exam

- **URL**: `/api/admin/exams`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "name": "Chemistry Quiz",
    "date": "2026-03-25",
    "status": "upcoming",
    "student_ids": [5, 6, 7]
  }
  ```
- **Field notes**:
  - `name` (required): string
  - `date` (required): date in `YYYY-MM-DD` format
  - `status` (optional): defaults to `upcoming`
  - `student_ids` (optional): array of valid student user IDs
- **Success Response**:
  ```json
  {
    "message": "Exam created successfully",
    "exam": {
      "id": 3,
      "name": "Chemistry Quiz",
      "date": "2026-03-25",
      "status": "upcoming",
      "created_at": "2026-02-20T12:00:00.000000Z",
      "updated_at": "2026-02-20T12:00:00.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 6,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 7,
          "username": "student3",
          "role": "student"
        }
      ],
      "status_history": [
        {
          "id": 1,
          "exam_id": 3,
          "old_status": null,
          "new_status": "upcoming",
          "changed_by_user_id": 1,
          "created_at": "2026-02-20T12:00:00.000000Z"
        }
      ]
    }
  }
  ```

## Get Exam Details

- **URL**: `/api/admin/exams/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Exam retrieved successfully",
    "exam": {
      "id": 1,
      "name": "Mathematics Midterm",
      "date": "2026-03-15",
      "status": "upcoming",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-01T10:00:00.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 5
          }
        },
        {
          "id": 6,
          "username": "student2",
          "role": "student",
          "pivot": {
            "exam_id": 1,
            "student_id": 6
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
          "created_at": "2026-02-01T10:00:00.000000Z"
        }
      ]
    }
  }
  ```

## Update Exam

- **URL**: `/api/admin/exams/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body** (all fields optional):
  ```json
  {
    "name": "Updated Mathematics Exam",
    "date": "2026-03-16",
    "status": "to_be_corrected"
  }
  ```
- **Field notes**:
  - `date`: must be `YYYY-MM-DD` format
  - `status`: one of `upcoming`, `to_be_corrected`, `passed`, `failed`. Status changes are automatically recorded in `status_history`.
- **Success Response**:
  ```json
  {
    "message": "Exam updated successfully",
    "exam": {
      "id": 1,
      "name": "Updated Mathematics Exam",
      "date": "2026-03-16",
      "status": "to_be_corrected",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-20T13:00:00.000000Z",
      "students": [],
      "status_history": [
        {
          "id": 1,
          "exam_id": 1,
          "old_status": null,
          "new_status": "upcoming",
          "changed_by_user_id": 1,
          "created_at": "2026-02-01T10:00:00.000000Z"
        },
        {
          "id": 2,
          "exam_id": 1,
          "old_status": "upcoming",
          "new_status": "to_be_corrected",
          "changed_by_user_id": 1,
          "created_at": "2026-02-20T13:00:00.000000Z"
        }
      ]
    }
  }
  ```

## Delete Exam

- **URL**: `/api/admin/exams/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Exam deleted successfully"
  }
  ```

## Enroll Students in Exam

- **URL**: `/api/admin/exams/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "student_ids": [5, 6, 8]
  }
  ```
- **Note**: Uses `syncWithoutDetaching` — already enrolled students are not duplicated.
- **Success Response**:
  ```json
  {
    "message": "Students enrolled in exam successfully",
    "exam": {
      "id": 1,
      "name": "Updated Mathematics Exam",
      "date": "2026-03-16",
      "status": "to_be_corrected",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-20T13:00:00.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 6,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 8,
          "username": "student4",
          "role": "student"
        }
      ]
    }
  }
  ```

## Remove Student from Exam

- **URL**: `/api/admin/exams/{examId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student removed from exam successfully",
    "exam": {
      "id": 1,
      "name": "Updated Mathematics Exam",
      "date": "2026-03-16",
      "status": "to_be_corrected",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-20T13:00:00.000000Z",
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 8,
          "username": "student4",
          "role": "student"
        }
      ]
    }
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Exam not found" }
  ```
  ```json
  { "message": "Student not found" }
  ```
  ```json
  { "message": "Student is not enrolled in this exam" }
  ```
