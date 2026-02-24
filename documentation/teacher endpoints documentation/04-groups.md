# Group Management

Teachers can create, view, update, and delete their own groups, as well as add and remove students.
All endpoints are scoped to the authenticated teacher — a teacher can only manage groups they own.

---

## Group Object

```json
{
  "group_id": 3,
  "group_name": "English A1 Monday",
  "group_teacher": 4,
  "description": "Beginner English group on Monday evenings.",
  "schedule_day": "Monday",
  "schedule_time": "18:00",
  "normal_schedule": "2026-02-24T18:00:00.000000Z",
  "formatted_schedule": "Monday at 18:00",
  "group_members": [12, 15, 19],
  "students": [
    {
      "id": 12,
      "username": "student1",
      "role": "student"
    }
  ],
  "created_at": "2026-01-10T09:00:00.000000Z",
  "updated_at": "2026-02-01T14:30:00.000000Z",
  "deleted_at": null
}
```

| Field | Description |
|-------|-------------|
| `group_id` | Unique group identifier |
| `group_name` | Display name of the group |
| `group_teacher` | User ID of the owning teacher |
| `description` | Optional free-text description |
| `schedule_day` | Day of the week the group meets |
| `schedule_time` | Time the group meets (`HH:MM` format) |
| `formatted_schedule` | Human-readable schedule (e.g. `Monday at 18:00`) |
| `group_members` | Array of student user IDs currently in the group |
| `students` | Full student objects (eager-loaded) |

---

## Get Schedule Options

Returns the allowed values for `schedule_day` and `schedule_time` fields.

- **URL**: `/api/teacher/groups/schedule/options`
- **Method**: `GET`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Schedule options retrieved successfully",
    "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
    "times": ["08:00", "08:30", "09:00", "09:30", "...", "20:00", "20:30"]
  }
  ```

---

## List My Groups

Returns all groups owned by the authenticated teacher.

- **URL**: `/api/teacher/groups`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Groups retrieved successfully",
    "groups": [
      {
        "group_id": 3,
        "group_name": "English A1 Monday",
        "schedule_day": "Monday",
        "schedule_time": "18:00",
        "formatted_schedule": "Monday at 18:00",
        "group_members": [12, 15],
        "students": [ ... ]
      }
    ]
  }
  ```

---

## Get Single Group

Returns a single group. Returns `403` if the group belongs to another teacher.

- **URL**: `/api/teacher/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Group retrieved successfully",
    "group": { ... }
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — group belongs to another teacher:
    ```json
    { "message": "Unauthorized" }
    ```

---

## Create Group

Creates a new group. The group is automatically assigned to the authenticated teacher.

- **URL**: `/api/teacher/groups`
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
    "group_name": "English A1 Monday",
    "description": "Beginner English group.",
    "schedule_day": "Monday",
    "schedule_time": "18:00",
    "group_members": [12, 15, 19]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `group_name` | string | Yes | Max 255 characters |
  | `description` | string | No | Free-text |
  | `schedule_day` | string | Yes | Must be a valid day (see Schedule Options) |
  | `schedule_time` | string | Yes | `HH:MM` format (e.g. `18:00`) |
  | `group_members` | array | No | Array of valid student user IDs |

- **Success Response** `201`:
  ```json
  {
    "message": "Group created successfully",
    "group": { ... }
  }
  ```

- **Error Responses**:
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "group_name": ["The group name field is required."],
        "schedule_day": ["The selected schedule day is invalid."],
        "schedule_time": ["The schedule time does not match the format H:i."]
      }
    }
    ```
  - **422** — one or more `group_members` IDs are not students:
    ```json
    { "message": "All group_members must be valid students" }
    ```

---

## Update Group

Updates fields on an existing group. All fields are optional — only provided fields are changed.
Providing `group_members` replaces the current student list entirely (sync).

- **URL**: `/api/teacher/groups/{id}`
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
    "group_name": "English A1 Tuesday",
    "schedule_day": "Tuesday",
    "schedule_time": "19:00"
  }
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Group updated successfully",
    "group": { ... }
  }
  ```

- **Error Responses**: same as Create Group (`404`, `403`, `422`).

---

## Delete Group

Soft-deletes a group. The group is no longer accessible but data is retained in the database.

- **URL**: `/api/teacher/groups/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  { "message": "Group deleted successfully" }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — group belongs to another teacher:
    ```json
    { "message": "Unauthorized" }
    ```

---

## Add Student to Group

Adds a single student to a group. Returns `409` if the student is already a member.

- **URL**: `/api/teacher/groups/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "student_id": 12 }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `student_id` | integer | Yes | Must be a valid user ID |

- **Success Response** `200`:
  ```json
  {
    "message": "Student added to group successfully",
    "group": { ... }
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — group belongs to another teacher:
    ```json
    { "message": "Unauthorized" }
    ```
  - **404** — `student_id` exists in `users` but is not a student:
    ```json
    { "message": "Student not found or user is not a student" }
    ```
  - **409** — student already in group:
    ```json
    { "message": "Student is already in this group" }
    ```
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": { "student_id": ["The selected student id is invalid."] }
    }
    ```

---

## Add Student to Group by Username

Same behaviour as the endpoint above, but looks up the student by their `username` instead of their ID.
Useful when the frontend has a name search rather than a user ID.

- **URL**: `/api/teacher/groups/{id}/students/by-username`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "username": "student1" }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `username` | string | Yes | Exact username of the student |

- **Success Response** `200`:
  ```json
  {
    "message": "Student added to group successfully",
    "group": { ... }
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — group belongs to another teacher:
    ```json
    { "message": "Unauthorized" }
    ```
  - **404** — username not found or belongs to a non-student user:
    ```json
    { "message": "Student not found or user is not a student" }
    ```
  - **409** — student already in group:
    ```json
    { "message": "Student is already in this group" }
    ```
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": { "username": ["The username field is required."] }
    }
    ```

---

## Remove Student from Group

Removes a student from a group.

- **URL**: `/api/teacher/groups/{groupId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Student removed from group successfully",
    "group": { ... }
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — group belongs to another teacher:
    ```json
    { "message": "Unauthorized" }
    ```
  - **404** — student is not in this group:
    ```json
    { "message": "Student is not in this group" }
    ```

---

## Schedule Reference

### Available Days

`Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `Sunday`

### Available Times

Times run from `08:00` to `20:30` in 30-minute increments:

```
08:00, 08:30, 09:00, 09:30, ..., 19:30, 20:00, 20:30
```

Use `GET /api/teacher/groups/schedule/options` to retrieve the full list programmatically.
