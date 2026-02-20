# Archive

This section covers the API endpoints for managing archived items (courses and groups) in the AB Academy platform.

## List Archived Courses

- **URL**: `/api/admin/archived/courses`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Archived courses retrieved successfully",
    "courses": [
      {
        "id": 4,
        "title": "Archived Mathematics Course",
        "description": "This course has been archived",
        "level": "beginner",
        "duration": 8,
        "price": 99.99,
        "is_active": false,
        "teacher_id": 2,
        "created_at": "2026-02-05T10:00:00.000000Z",
        "updated_at": "2026-02-15T10:00:00.000000Z",
        "deleted_at": "2026-02-15T10:00:00.000000Z",
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        }
      }
    ]
  }
  ```

## List Archived Groups

- **URL**: `/api/admin/archived/groups`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Archived groups retrieved successfully",
    "groups": [
      {
        "group_id": 3,
        "group_name": "Archived Science Group",
        "group_teacher": 3,
        "description": "This group has been archived",
        "schedule_day": "Monday",
        "schedule_time": "14:30",
        "formatted_schedule": "Monday at 14:30",
        "created_at": "2026-02-05T11:00:00.000000Z",
        "updated_at": "2026-02-15T11:00:00.000000Z",
        "deleted_at": "2026-02-15T11:00:00.000000Z",
        "teacher": {
          "id": 3,
          "username": "teacher2",
          "role": "teacher"
        }
      }
    ]
  }
  ```

## Restore Archived Course

- **URL**: `/api/admin/archived/courses/{id}/restore`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Course restored successfully",
    "course": {
      "id": 4,
      "title": "Archived Mathematics Course",
      "description": "This course has been archived",
      "level": "beginner",
      "duration": 8,
      "price": 99.99,
      "is_active": false,
      "teacher_id": 2,
      "created_at": "2026-02-05T10:00:00.000000Z",
      "updated_at": "2026-02-20T14:00:00.000000Z",
      "deleted_at": null,
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Restore Archived Group

- **URL**: `/api/admin/archived/groups/{id}/restore`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group restored successfully",
    "group": {
      "group_id": 3,
      "group_name": "Archived Science Group",
      "group_teacher": 3,
      "description": "This group has been archived",
      "schedule_day": "Monday",
      "schedule_time": "14:30",
      "formatted_schedule": "Monday at 14:30",
      "created_at": "2026-02-05T11:00:00.000000Z",
      "updated_at": "2026-02-20T14:00:00.000000Z",
      "deleted_at": null,
      "teacher": {
        "id": 3,
        "username": "teacher2",
        "role": "teacher"
      }
    }
  }
  ```
