# Groups

This section covers the API endpoints for managing student groups in the AB Academy platform.

> **Note**: `schedule_days` is an array of `{ day, time, duration }` objects — a group can have multiple session slots per week. Times use 24-hour `HH:MM` format. `duration` is in minutes.

> **Note**: The `class_code` field is a unique 8-character alphanumeric code that students use to join a group. It is `null` until the owning teacher generates one via `POST /api/teacher/groups/{id}/generate-code`. Admins have read-only visibility of this field.

## List All Groups

- **URL**: `/api/admin/groups`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Groups retrieved successfully",
    "groups": [
      {
        "group_id": 1,
        "group_name": "Math Group",
        "group_teacher": 2,
        "description": "Advanced mathematics study group",
        "class_code": "AB12CD34",
        "schedule_days": [
          { "day": "Monday",    "time": "14:30", "duration": 90 },
          { "day": "Wednesday", "time": "14:30", "duration": 90 }
        ],
        "formatted_schedule": "Monday at 14:30 (90min), Wednesday at 14:30 (90min)",
        "total_weekly_minutes": 180,
        "group_members": [3, 4, 5],
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "students": [
          { "id": 3, "username": "student1", "role": "student" },
          { "id": 4, "username": "student2", "role": "student" },
          { "id": 5, "username": "student3", "role": "student" }
        ]
      }
    ]
  }
  ```

## Get Schedule Options

- **URL**: `/api/admin/groups/schedule/options`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Schedule options retrieved successfully",
    "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
    "times": ["08:00", "08:30", "09:00", "09:30", "10:00", "10:30", "11:00", "11:30", "12:00", "12:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00", "17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]
  }
  ```

## Create Group

- **URL**: `/api/admin/groups`
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
    "group_name": "Science Group",
    "group_teacher": 2,
    "description": "Physics and chemistry study group",
    "schedule_days": [
      { "day": "Tuesday",  "time": "15:30", "duration": 90 },
      { "day": "Thursday", "time": "15:30", "duration": 90 }
    ],
    "group_members": [3, 4, 5]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `group_name` | string | Yes | Max 255 characters |
  | `group_teacher` | integer | Yes | Must be a valid teacher user ID |
  | `description` | string | No | Free-text |
  | `schedule_days` | array | Yes | At least one entry required |
  | `schedule_days[].day` | string | Yes | Must be a valid day (see Schedule Options) |
  | `schedule_days[].time` | string | Yes | `HH:MM` 24-hour format |
  | `schedule_days[].duration` | integer | Yes | Session length in minutes (e.g. `90`) |
  | `group_members` | array | No | Array of valid student user IDs |

- **Success Response**:
  ```json
  {
    "message": "Group created successfully",
    "group": {
      "group_id": 2,
      "group_name": "Science Group",
      "group_teacher": 2,
      "description": "Physics and chemistry study group",
      "class_code": null,
      "schedule_days": [
        { "day": "Tuesday",  "time": "15:30", "duration": 90 },
        { "day": "Thursday", "time": "15:30", "duration": 90 }
      ],
      "formatted_schedule": "Tuesday at 15:30 (90min), Thursday at 15:30 (90min)",
      "total_weekly_minutes": 180,
      "group_members": [3, 4, 5]
    }
  }
  ```

## Get Group Details

- **URL**: `/api/admin/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group retrieved successfully",
    "group": {
      "group_id": 2,
      "group_name": "Science Group",
      "group_teacher": 2,
      "description": "Physics and chemistry study group",
      "class_code": "AB12CD34",
      "schedule_days": [
        { "day": "Tuesday",  "time": "15:30", "duration": 90 },
        { "day": "Thursday", "time": "15:30", "duration": 90 }
      ],
      "formatted_schedule": "Tuesday at 15:30 (90min), Thursday at 15:30 (90min)",
      "total_weekly_minutes": 180,
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        { "id": 3, "username": "student1", "role": "student" },
        { "id": 4, "username": "student2", "role": "student" },
        { "id": 5, "username": "student3", "role": "student" }
      ]
    }
  }
  ```

## Update Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body** (all fields optional):
  ```json
  {
    "group_name": "Updated Science Group",
    "description": "Updated description",
    "schedule_days": [
      { "day": "Wednesday", "time": "16:00", "duration": 60 }
    ]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group updated successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "class_code": "AB12CD34",
      "schedule_days": [
        { "day": "Wednesday", "time": "16:00", "duration": 60 }
      ],
      "formatted_schedule": "Wednesday at 16:00 (60min)",
      "total_weekly_minutes": 60,
      "group_members": [3, 4, 5]
    }
  }
  ```

## Delete Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group deleted successfully"
  }
  ```

## Add Student to Group

- **URL**: `/api/admin/groups/{id}/students`
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
    "student_id": 6
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student added to group successfully"
  }
  ```

## Remove Student from Group

- **URL**: `/api/admin/groups/{groupId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student removed from group successfully",
    "group": { ... }
  }
  ```

## Update Group Members

- **URL**: `/api/admin/groups/{id}/members`
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
    "group_members": [3, 5, 7, 8]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group members updated successfully",
    "group": { ... }
  }
  ```
