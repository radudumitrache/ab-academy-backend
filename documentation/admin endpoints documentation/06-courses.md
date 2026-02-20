# Courses

This section covers the API endpoints for managing courses in the AB Academy platform.

## List All Courses

- **URL**: `/api/admin/courses`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Courses retrieved successfully",
    "courses": [
      {
        "id": 1,
        "title": "Introduction to Mathematics",
        "description": "Basic mathematics concepts for beginners",
        "level": "beginner",
        "duration": 8,
        "price": 99.99,
        "is_active": true,
        "teacher_id": 2,
        "created_at": "2026-02-01T10:00:00.000000Z",
        "updated_at": "2026-02-01T10:00:00.000000Z",
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        }
      },
      {
        "id": 2,
        "title": "Advanced Physics",
        "description": "Complex physics concepts for advanced students",
        "level": "advanced",
        "duration": 12,
        "price": 149.99,
        "is_active": true,
        "teacher_id": 3,
        "created_at": "2026-02-02T11:00:00.000000Z",
        "updated_at": "2026-02-02T11:00:00.000000Z",
        "teacher": {
          "id": 3,
          "username": "teacher2",
          "role": "teacher"
        }
      }
    ]
  }
  ```

## Create Course

- **URL**: `/api/admin/courses`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Chemistry Fundamentals",
    "description": "Introduction to basic chemistry principles",
    "level": "intermediate",
    "duration": 10,
    "price": 129.99,
    "is_active": true,
    "teacher_id": 2
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Course created successfully",
    "course": {
      "id": 3,
      "title": "Chemistry Fundamentals",
      "description": "Introduction to basic chemistry principles",
      "level": "intermediate",
      "duration": 10,
      "price": 129.99,
      "is_active": true,
      "teacher_id": 2,
      "created_at": "2026-02-20T12:00:00.000000Z",
      "updated_at": "2026-02-20T12:00:00.000000Z",
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Get Course Details

- **URL**: `/api/admin/courses/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Course retrieved successfully",
    "course": {
      "id": 1,
      "title": "Introduction to Mathematics",
      "description": "Basic mathematics concepts for beginners",
      "level": "beginner",
      "duration": 8,
      "price": 99.99,
      "is_active": true,
      "teacher_id": 2,
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-01T10:00:00.000000Z",
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Update Course

- **URL**: `/api/admin/courses/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Mathematics Course",
    "description": "Updated description",
    "level": "intermediate",
    "duration": 10,
    "price": 119.99,
    "is_active": true,
    "teacher_id": 3
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Course updated successfully",
    "course": {
      "id": 1,
      "title": "Updated Mathematics Course",
      "description": "Updated description",
      "level": "intermediate",
      "duration": 10,
      "price": 119.99,
      "is_active": true,
      "teacher_id": 3,
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-20T13:00:00.000000Z",
      "teacher": {
        "id": 3,
        "username": "teacher2",
        "role": "teacher"
      }
    }
  }
  ```

## Delete Course

- **URL**: `/api/admin/courses/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Course deleted successfully"
  }
  ```
