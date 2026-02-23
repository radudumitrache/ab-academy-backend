# Groups

This section covers the API endpoints for managing student groups in the AB Academy platform.

> **Note**: The `schedule_time` field is formatted as a time string in 24-hour format (HH:MM). For example, "14:30" represents 2:30 PM.

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
        "schedule_day": "Monday",
        "schedule_time": "14:30",
        "formatted_schedule": "Monday at 14:30",
        "group_members": [3, 4, 5],
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "students": [
          {
            "id": 3,
            "username": "student1",
            "role": "student"
          },
          {
            "id": 4,
            "username": "student2",
            "role": "student"
          },
          {
            "id": 5,
            "username": "student3",
            "role": "student"
          }
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
  ```
- **Request Body**:
  ```json
  {
    "group_name": "Science Group",
    "group_teacher": 2,
    "description": "Physics and chemistry study group",
    "schedule_day": "Tuesday",
    "schedule_time": "15:30",  // Time in 24-hour format (HH:MM)
    "group_members": [3, 4, 5]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group created successfully",
    "group": {
      "group_id": 2,
      "group_name": "Science Group",
      "group_teacher": 2,
      "description": "Physics and chemistry study group",
      "schedule_day": "Tuesday",
      "schedule_time": "15:30",
      "formatted_schedule": "Tuesday at 15:30",
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
      "schedule_day": "Tuesday",
      "schedule_time": "15:30",
      "formatted_schedule": "Tuesday at 15:30",
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        }
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
  ```
- **Request Body**:
  ```json
  {
    "group_name": "Updated Science Group",
    "description": "Updated description",
    "schedule_day": "Wednesday",
    "schedule_time": "16:00"  // Time in 24-hour format (HH:MM)
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
      "schedule_day": "Wednesday",
      "schedule_time": "16:00",
      "formatted_schedule": "Wednesday at 16:00",
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
    "message": "Student added to group successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "schedule_day": "Wednesday",
      "schedule_time": "16:00",
      "formatted_schedule": "Wednesday at 16:00",
      "group_members": [3, 4, 5, 6]
    }
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
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "schedule_day": "Wednesday",
      "schedule_time": "16:00",
      "formatted_schedule": "Wednesday at 16:00",
      "group_members": [3, 4, 5]
    }
  }
  ```

## Update Group Members

- **URL**: `/api/admin/groups/{id}/members`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
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
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "schedule_day": "Wednesday",
      "schedule_time": "16:00",
      "formatted_schedule": "Wednesday at 16:00",
      "group_members": [3, 5, 7, 8]
    }
  }
  ```
