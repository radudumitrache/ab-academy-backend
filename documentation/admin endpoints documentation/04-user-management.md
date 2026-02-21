# User Management

This section covers the API endpoints for managing users (teachers and students) in the AB Academy platform.

## Teacher Management

### List All Teachers

- **URL**: `/api/admin/teachers`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teachers retrieved successfully",
    "count": 2,
    "teachers": [
      {
        "id": 2,
        "username": "teacher1",
        "email": "teacher1@example.com",
        "telephone": "+1234567890",
        "role": "teacher",
        "created_at": "2026-02-01T10:00:00.000000Z"
      },
      {
        "id": 3,
        "username": "teacher2",
        "email": "teacher2@example.com",
        "telephone": "+0987654321",
        "role": "teacher",
        "created_at": "2026-02-02T11:00:00.000000Z"
      }
    ]
  }
  ```

### Create Teacher

- **URL**: `/api/admin/teachers`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "new_teacher",
    "email": "new_teacher@example.com",
    "telephone": "+1122334455",
    "password": "secure_password"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher created successfully",
    "teacher": {
      "id": 4,
      "username": "new_teacher",
      "email": "new_teacher@example.com",
      "telephone": "+1122334455",
      "role": "teacher",
      "created_at": "2026-02-20T12:00:00.000000Z"
    }
  }
  ```

### Get Teacher Details

- **URL**: `/api/admin/teachers/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher retrieved successfully",
    "teacher": {
      "id": 2,
      "username": "teacher1",
      "email": "teacher1@example.com",
      "telephone": "+1234567890",
      "role": "teacher",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-01T10:00:00.000000Z"
    },
    "teaching_stats": {
      "total_students": 15,
      "total_groups": 3,
      "total_exams": 5
    },
    "created_groups": [
      {
        "id": 1,
        "group_name": "Math Group",
        "description": "Advanced mathematics group",
        "schedule_day": "Monday",
        "schedule_time": "14:30",
        "formatted_schedule": "Monday at 14:30",
        "students_count": 6,
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
          },
          {
            "id": 8,
            "username": "student4",
            "role": "student"
          },
          {
            "id": 9,
            "username": "student5",
            "role": "student"
          },
          {
            "id": 10,
            "username": "student6",
            "role": "student"
          }
        ]
      },
      {
        "id": 4,
        "group_name": "Calculus Group",
        "description": "Advanced calculus study group",
        "schedule_day": "Thursday",
        "schedule_time": "16:00",
        "formatted_schedule": "Thursday at 16:00",
        "students_count": 5,
        "students": [
          {
            "id": 7,
            "username": "student3",
            "role": "student"
          },
          {
            "id": 8,
            "username": "student4",
            "role": "student"
          },
          {
            "id": 11,
            "username": "student7",
            "role": "student"
          },
          {
            "id": 12,
            "username": "student8",
            "role": "student"
          },
          {
            "id": 13,
            "username": "student9",
            "role": "student"
          }
        ]
      },
      {
        "id": 6,
        "group_name": "Statistics Group",
        "description": "Introduction to statistics",
        "schedule_day": "Friday",
        "schedule_time": "10:00",
        "formatted_schedule": "Friday at 10:00",
        "students_count": 4,
        "students": [
          {
            "id": 14,
            "username": "student10",
            "role": "student"
          },
          {
            "id": 15,
            "username": "student11",
            "role": "student"
          },
          {
            "id": 16,
            "username": "student12",
            "role": "student"
          },
          {
            "id": 17,
            "username": "student13",
            "role": "student"
          }
        ]
      }
    ]
  }
  ```

### Update Teacher

- **URL**: `/api/admin/teachers/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "updated_teacher",
    "email": "updated_teacher@example.com",
    "telephone": "+9988776655",
    "password": "new_password",
    "admin_notes": "Teacher specializes in advanced calculus and statistics."
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher updated successfully",
    "teacher": {
      "id": 2,
      "username": "updated_teacher",
      "email": "updated_teacher@example.com",
      "telephone": "+9988776655",
      "role": "teacher",
      "updated_at": "2026-02-20T13:00:00.000000Z"
    }
  }
  ```

### Delete Teacher

- **URL**: `/api/admin/teachers/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher 'teacher1' deleted successfully"
  }
  ```

## Student Management

### List All Students

- **URL**: `/api/admin/students`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Students retrieved successfully",
    "count": 3,
    "students": [
      {
        "id": 5,
        "username": "student1",
        "email": "student1@example.com",
        "telephone": "+1212121212",
        "role": "student",
        "created_at": "2026-02-05T10:00:00.000000Z"
      },
      {
        "id": 6,
        "username": "student2",
        "email": "student2@example.com",
        "telephone": "+2323232323",
        "role": "student",
        "created_at": "2026-02-06T11:00:00.000000Z"
      },
      {
        "id": 7,
        "username": "student3",
        "email": "student3@example.com",
        "telephone": "+3434343434",
        "role": "student",
        "created_at": "2026-02-07T12:00:00.000000Z"
      }
    ]
  }
  ```

### Create Student

- **URL**: `/api/admin/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "new_student",
    "email": "new_student@example.com",
    "telephone": "+4545454545",
    "password": "secure_password"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student created successfully",
    "student": {
      "id": 8,
      "username": "new_student",
      "email": "new_student@example.com",
      "telephone": "+4545454545",
      "role": "student",
      "created_at": "2026-02-20T14:00:00.000000Z"
    }
  }
  ```

### Get Student Details

- **URL**: `/api/admin/students/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
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
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-05T10:00:00.000000Z",
      "admin_notes": "Student has requested extra help with mathematics."
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
      },
      {
        "id": 3,
        "group_name": "Physics Group",
        "group_teacher": 3,
        "description": "Physics study group",
        "schedule_day": "Wednesday",
        "schedule_time": "16:00",
        "formatted_schedule": "Wednesday at 16:00",
        "teacher": {
          "id": 3,
          "username": "teacher2",
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
      },
      {
        "id": 2,
        "name": "Physics Quiz",
        "date": "2026-02-28T14:00:00.000000Z",
        "status": "completed",
        "teacher": {
          "id": 3,
          "username": "teacher2",
          "role": "teacher"
        },
        "score": 85,
        "feedback": "Good work on the theoretical questions."
      }
    ]
  }
  ```

### Update Student

- **URL**: `/api/admin/students/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "updated_student",
    "email": "updated_student@example.com",
    "telephone": "+5656565656",
    "password": "new_password",
    "admin_notes": "Student has requested extra help with mathematics."
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student updated successfully",
    "student": {
      "id": 5,
      "username": "updated_student",
      "email": "updated_student@example.com",
      "telephone": "+5656565656",
      "role": "student",
      "updated_at": "2026-02-20T15:00:00.000000Z"
    }
  }
  ```

### Delete Student

- **URL**: `/api/admin/students/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student 'student1' deleted successfully"
  }
  ```
