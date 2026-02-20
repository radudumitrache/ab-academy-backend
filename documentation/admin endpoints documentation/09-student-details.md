# Student Details

This section covers the API endpoints for accessing detailed information about students in the AB Academy platform.

## Get Student Groups

Retrieves all groups a specific student belongs to.

- **URL**: `/api/admin/students/{id}/groups`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the student

- **Success Response**:
  ```json
  {
    "message": "Student groups retrieved successfully",
    "student_id": 5,
    "groups": [
      {
        "group_id": 1,
        "group_name": "Math Group",
        "group_teacher": 2,
        "description": "Advanced mathematics group",
        "schedule_day": "Monday",
        "schedule_time": "14:30",
        "formatted_schedule": "Monday at 14:30",
        "created_at": "2026-02-15T10:20:30.000000Z",
        "updated_at": "2026-02-15T10:20:30.000000Z",
        "teacher": {
          "id": 2,
          "username": "teacher_name",
          "email": "teacher@example.com",
          "role": "teacher"
        }
      }
    ]
  }
  ```

## Get Student Exams

Retrieves exam data for a specific student, including upcoming, completed, and exams to be graded.

- **URL**: `/api/admin/students/{id}/exams`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the student

- **Success Response**:
  ```json
  {
    "message": "Student exams retrieved successfully",
    "student_id": 5,
    "exams_summary": {
      "upcoming_count": 2,
      "completed_count": 3,
      "to_be_graded_count": 1,
      "next_exam": {
        "id": 8,
        "name": "Algebra Final",
        "date": "2026-03-15T09:00:00.000000Z",
        "status": "upcoming",
        "teacher": {
          "id": 2,
          "username": "teacher_name"
        },
        "score": null,
        "feedback": null
      }
    },
    "exams": {
      "upcoming": [
        {
          "id": 8,
          "name": "Algebra Final",
          "date": "2026-03-15T09:00:00.000000Z",
          "status": "upcoming",
          "teacher": {
            "id": 2,
            "username": "teacher_name"
          },
          "score": null,
          "feedback": null
        }
      ],
      "completed": [
        {
          "id": 5,
          "name": "Calculus Midterm",
          "date": "2026-02-10T09:00:00.000000Z",
          "status": "passed",
          "teacher": {
            "id": 2,
            "username": "teacher_name"
          },
          "score": 85.5,
          "feedback": "Good work on the derivatives section."
        }
      ],
      "to_be_graded": [
        {
          "id": 7,
          "name": "Statistics Quiz",
          "date": "2026-02-18T14:00:00.000000Z",
          "status": "to_be_corrected",
          "teacher": {
            "id": 3,
            "username": "another_teacher"
          },
          "score": null,
          "feedback": null
        }
      ]
    }
  }
  ```

## Get Student Payments

Retrieves payment information for a specific student.

- **URL**: `/api/admin/students/{id}/payments`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the student

- **Success Response**:
  ```json
  {
    "message": "Student payments retrieved successfully",
    "student_id": 5,
    "payments": {
      "status": "No payment records found",
      "data": []
    },
    "note": "Payment system not yet implemented"
  }
  ```

## Get User Notes

Retrieves admin notes for a specific user (student or teacher).

- **URL**: `/api/admin/users/{id}/notes`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the user

- **Success Response**:
  ```json
  {
    "message": "User notes retrieved successfully",
    "user_id": 5,
    "notes": [
      {
        "id": 3,
        "user_id": 5,
        "created_by": 1,
        "content": "Student has requested extra help with calculus.",
        "created_at": "2026-02-18T15:30:45.000000Z",
        "updated_at": "2026-02-18T15:30:45.000000Z",
        "creator": {
          "id": 1,
          "username": "admin_user",
          "role": "admin"
        }
      }
    ]
  }
  ```

## Save User Note

Saves a new admin note for a specific user (student or teacher).

- **URL**: `/api/admin/users/{id}/notes`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the user
- **Request Body**:
  ```json
  {
    "content": "Student has requested extra help with calculus."
  }
  ```

- **Success Response**:
  ```json
  {
    "message": "Note saved successfully",
    "note": {
      "id": 3,
      "user_id": 5,
      "created_by": 1,
      "content": "Student has requested extra help with calculus.",
      "created_at": "2026-02-18T15:30:45.000000Z",
      "updated_at": "2026-02-18T15:30:45.000000Z",
      "creator": {
        "id": 1,
        "username": "admin_user",
        "role": "admin"
      }
    }
  }
  ```
