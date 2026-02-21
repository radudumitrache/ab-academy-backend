# Student Details

This section covers the API endpoints for accessing detailed information about students in the AB Academy platform.

## Get Student Details

Retrieves detailed information about a student, including their groups and exams.

- **URL**: `/api/admin/students/{id}`
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
    "message": "Student retrieved successfully",
    "student": {
      "id": 5,
      "username": "student1",
      "email": "student1@example.com",
      "telephone": "+1212121212",
      "role": "student",
      "admin_notes": "Student has requested extra help with mathematics.",
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-05T10:00:00.000000Z"
    },
    "enrolled_groups": [
      {
        "id": 1,
        "group_name": "Math Group",
        "group_teacher": 2,
        "description": "Advanced mathematics group",
        "schedule_day": "Monday",
        "schedule_time": "14:30",
        "formatted_schedule": "Monday at 14:30",
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        }
      }
    ],
    "enrolled_exams": [
      {
        "id": 1,
        "name": "Mathematics Midterm",
        "date": "2026-03-15T10:00:00.000000Z",
        "status": "upcoming",
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "score": null,
        "feedback": null
      }
    ],
    "invoices": [
      {
        "id": 1,
        "title": "Course Payment",
        "series": "INV",
        "number": "000001",
        "value": "499.99",
        "currency": "EUR",
        "due_date": "2026-03-15",
        "status": "paid",
        "created_at": "2026-02-21T10:00:00.000000Z",
        "updated_at": "2026-02-21T10:00:00.000000Z"
      },
      {
        "id": 3,
        "title": "Course Materials",
        "series": "INV",
        "number": "000003",
        "value": "75.50",
        "currency": "EUR",
        "due_date": "2026-04-01",
        "status": "draft",
        "created_at": "2026-02-21T12:00:00.000000Z",
        "updated_at": "2026-02-21T12:00:00.000000Z"
      }
    ]
  }
  ```

## Note on Student Data

The student details endpoint now includes all the necessary information about a student, including:

- Basic student information
- Admin notes
- Enrolled groups with schedule information
- Enrolled exams with scores and feedback
- Student invoices with payment status

This consolidated approach reduces the number of API calls needed to build a complete student profile view.

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
