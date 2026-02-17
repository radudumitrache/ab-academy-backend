## Groups

Groups can be managed through admin routes. The following endpoints are available:

### List All Groups

- **URL**: `/api/admin/groups`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
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
        "normal_schedule": "2026-03-01",
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

### Create Group

- **URL**: `/api/admin/groups`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
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
    "normal_schedule": "2026-03-02",
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
      "normal_schedule": "2026-03-02",
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

### Get Group Details

- **URL**: `/api/admin/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
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
      "normal_schedule": "2026-03-02",
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

### Update Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "group_name": "Updated Science Group",
    "description": "Updated description"
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
      "normal_schedule": "2026-03-02",
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

### Delete Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
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

### Add Student to Group

- **URL**: `/api/admin/groups/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
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
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5, 6],
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
        },
        {
          "id": 6,
          "username": "student4",
          "role": "student"
        }
      ]
    }
  }
  ```

### Remove Student from Group

- **URL**: `/api/admin/groups/{groupId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
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
      "normal_schedule": "2026-03-02",
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

### Update Group Members

- **URL**: `/api/admin/groups/{id}/group-members`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "group_members": [3, 4, 7, 8]
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
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 7, 8],
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
          "id": 7,
          "username": "student5",
          "role": "student"
        },
        {
          "id": 8,
          "username": "student6",
          "role": "student"
        }
      ]
    }
  }
  ```
