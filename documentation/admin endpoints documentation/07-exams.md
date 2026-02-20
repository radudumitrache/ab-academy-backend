# Exams

This section covers the API endpoints for managing exams in the AB Academy platform.

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
    "exams": [
      {
        "id": 1,
        "name": "Mathematics Midterm",
        "date": "2026-03-15T10:00:00.000000Z",
        "teacher_id": 2,
        "status": "upcoming",
        "created_at": "2026-02-01T10:00:00.000000Z",
        "updated_at": "2026-02-01T10:00:00.000000Z",
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "students": [
          {
            "id": 5,
            "username": "student1",
            "role": "student",
            "pivot": {
              "score": null,
              "feedback": null
            }
          },
          {
            "id": 6,
            "username": "student2",
            "role": "student",
            "pivot": {
              "score": null,
              "feedback": null
            }
          }
        ]
      },
      {
        "id": 2,
        "name": "Physics Final",
        "date": "2026-03-20T14:00:00.000000Z",
        "teacher_id": 3,
        "status": "upcoming",
        "created_at": "2026-02-02T11:00:00.000000Z",
        "updated_at": "2026-02-02T11:00:00.000000Z",
        "teacher": {
          "id": 3,
          "username": "teacher2",
          "role": "teacher"
        },
        "students": [
          {
            "id": 7,
            "username": "student3",
            "role": "student",
            "pivot": {
              "score": null,
              "feedback": null
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
    "date": "2026-03-25T09:00:00",
    "teacher_id": 2,
    "status": "upcoming",
    "students": [5, 6, 7]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Exam created successfully",
    "exam": {
      "id": 3,
      "name": "Chemistry Quiz",
      "date": "2026-03-25T09:00:00.000000Z",
      "teacher_id": 2,
      "status": "upcoming",
      "created_at": "2026-02-20T12:00:00.000000Z",
      "updated_at": "2026-02-20T12:00:00.000000Z",
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
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
      "date": "2026-03-15T10:00:00.000000Z",
      "teacher_id": 2,
      "status": "upcoming",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-01T10:00:00.000000Z",
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 5,
          "username": "student1",
          "role": "student",
          "pivot": {
            "score": null,
            "feedback": null
          }
        },
        {
          "id": 6,
          "username": "student2",
          "role": "student",
          "pivot": {
            "score": null,
            "feedback": null
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
          "created_at": "2026-02-01T10:00:00.000000Z",
          "updated_at": "2026-02-01T10:00:00.000000Z",
          "user": {
            "id": 1,
            "username": "admin",
            "role": "admin"
          }
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
- **Request Body**:
  ```json
  {
    "name": "Updated Mathematics Exam",
    "date": "2026-03-16T11:00:00",
    "status": "in_progress"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Exam updated successfully",
    "exam": {
      "id": 1,
      "name": "Updated Mathematics Exam",
      "date": "2026-03-16T11:00:00.000000Z",
      "teacher_id": 2,
      "status": "in_progress",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-20T13:00:00.000000Z"
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
    "students": [5, 6, 8]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Students enrolled in exam successfully",
    "exam": {
      "id": 1,
      "name": "Updated Mathematics Exam",
      "date": "2026-03-16T11:00:00.000000Z",
      "teacher_id": 2,
      "status": "in_progress",
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
      "date": "2026-03-16T11:00:00.000000Z",
      "teacher_id": 2,
      "status": "in_progress",
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
