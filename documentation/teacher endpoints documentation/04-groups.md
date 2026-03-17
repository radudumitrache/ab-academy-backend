# Group Management

Teachers can create, view, update, and delete their own groups, as well as add and remove students.
A teacher can manage groups they **own** or groups where they are an **assistant teacher**.
Only the main (owner) teacher can invite or remove assistant teachers.

---

## Group Object

```json
{
  "group_id": 3,
  "group_name": "English A1",
  "group_teacher": 4,
  "description": "Beginner English group.",
  "class_code": "AB12CD34",
  "schedule_days": [
    { "day": "Monday",    "time": "18:00", "duration": 90 },
    { "day": "Wednesday", "time": "18:00", "duration": 90 }
  ],
  "formatted_schedule": "Monday at 18:00 (90min), Wednesday at 18:00 (90min)",
  "total_weekly_minutes": 180,
  "group_members": [12, 15, 19],
  "assistant_teacher_ids": [7, 9],
  "students": [
    {
      "id": 12,
      "username": "student1",
      "role": "student"
    }
  ],
  "assistant_teachers": [
    { "id": 7, "username": "teacher_ion", "role": "teacher" },
    { "id": 9, "username": "teacher_maria", "role": "teacher" }
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
| `class_code` | 8-character alphanumeric code students use to join the group (`null` until generated) |
| `schedule_days` | Array of `{ day, time, duration }` objects — one entry per session slot |
| `schedule_days[].day` | Day of the week (e.g. `Monday`) |
| `schedule_days[].time` | Start time in `HH:MM` 24-hour format |
| `schedule_days[].duration` | Session length in minutes (integer) |
| `formatted_schedule` | Human-readable schedule (e.g. `Monday at 18:00 (90min), Wednesday at 18:00 (90min)`) |
| `total_weekly_minutes` | Sum of all session durations per week |
| `group_members` | Array of student user IDs currently in the group |
| `assistant_teacher_ids` | Array of assistant teacher user IDs |
| `assistant_teachers` | Full assistant teacher objects (eager-loaded) |
| `students` | Full student objects (eager-loaded) |

---

## Get Schedule Options

Returns the allowed values for `schedule_days[].day` and `schedule_days[].time`.

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

Returns all groups where the authenticated teacher is the owner **or** an assistant teacher.

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
        "group_name": "English A1",
        "class_code": "AB12CD34",
        "schedule_days": [
          { "day": "Monday",    "time": "18:00" },
          { "day": "Wednesday", "time": "18:00" }
        ],
        "formatted_schedule": "Monday at 18:00, Wednesday at 18:00",
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
    "group_name": "English A1",
    "description": "Beginner English group.",
    "schedule_days": [
      { "day": "Monday",    "time": "18:00", "duration": 90 },
      { "day": "Wednesday", "time": "18:00", "duration": 90 }
    ],
    "group_members": [12, 15, 19]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `group_name` | string | Yes | Max 255 characters |
  | `description` | string | No | Free-text |
  | `schedule_days` | array | Yes | At least one entry required |
  | `schedule_days[].day` | string | Yes | Must be a valid day (see Schedule Options) |
  | `schedule_days[].time` | string | Yes | `HH:MM` 24-hour format (e.g. `18:00`) |
  | `schedule_days[].duration` | integer | Yes | Session length in minutes (e.g. `90`) |
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
        "schedule_days": ["The schedule days field is required."],
        "schedule_days.0.day": ["The selected schedule_days.0.day is invalid."],
        "schedule_days.0.time": ["The schedule_days.0.time does not match the format H:i."]
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
Providing `schedule_days` replaces the entire schedule. Providing `group_members` replaces the current student list entirely (sync).

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
    "group_name": "English A1 — updated",
    "schedule_days": [
      { "day": "Tuesday",  "time": "19:00", "duration": 60 },
      { "day": "Thursday", "time": "19:00", "duration": 60 }
    ]
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

## Generate Class Code

Generates (or regenerates) a unique 8-character class code for the group. Students can then join the group by entering this code.
Calling this endpoint again replaces the existing code with a new one.

- **URL**: `/api/teacher/groups/{id}/generate-code`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Class code generated successfully",
    "class_code": "AB12CD34"
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

## Take Attendance

Records attendance for a group session. Can be called multiple times for the same session — subsequent calls overwrite previous records for the same student/session combination.

Each student entry must have one of three statuses: `present`, `absent`, or `motivated_absent`.

- **URL**: `/api/teacher/groups/{id}/attendance`
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
    "session_date": "2026-03-10",
    "session_time": "18:00",
    "attendance": [
      { "student_id": 12, "status": "present" },
      { "student_id": 15, "status": "absent" },
      { "student_id": 19, "status": "motivated_absent" }
    ]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `session_date` | string | Yes | `YYYY-MM-DD` format |
  | `session_time` | string | Yes | `HH:MM` 24-hour format — should match a slot in `schedule_days` |
  | `attendance` | array | Yes | At least one entry |
  | `attendance[].student_id` | integer | Yes | Must be a member of this group |
  | `attendance[].status` | string | Yes | `present`, `absent`, or `motivated_absent` |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance recorded successfully",
    "session_date": "2026-03-10",
    "session_time": "18:00",
    "attendance": [
      { "student_id": 12, "status": "present" },
      { "student_id": 15, "status": "absent" },
      { "student_id": 19, "status": "motivated_absent" }
    ]
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
  - **422** — student is not a group member:
    ```json
    { "message": "Student 99 is not a member of this group" }
    ```
  - **422** — validation failed:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "attendance.0.status": ["The selected attendance.0.status is invalid."]
      }
    }
    ```

---

## Join a Group as Assistant Teacher

`POST /api/teacher/groups/join`

A teacher can enter a group's class code to become an **assistant teacher** of that group.
This is the teacher equivalent of a student joining via class code — instead of becoming a student member, the teacher is added to the assistant teachers list.

**Request body**:
```json
{ "class_code": "AB12CD34" }
```

**Response** `200`:
```json
{
  "message": "Joined group as assistant teacher successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — invalid class code
- `409` — you are the group owner: `{ "message": "You are already the owner of this group" }`
- `409` — already an assistant: `{ "message": "You are already an assistant teacher in this group" }`
- `422` — validation failed

---

## Add Assistant Teacher

Invites a teacher to be an assistant for this group by their user ID. **Only the main (owner) teacher can call this.**
Assistant teachers gain the same management permissions as the owner (edit group, add/remove students, take attendance, assign homework).

- **URL**: `/api/teacher/groups/{id}/assistant-teachers`
- **Method**: `POST`
- **Auth Required**: Yes (must be the group owner)
- **Request Body**:
  ```json
  { "teacher_id": 7 }
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Assistant teacher added successfully",
    "group": { ...group object... }
  }
  ```

- **Error Responses**:
  - **404** — group not found
  - **403** — you are not the group owner
  - **404** — `teacher_id` is not a valid teacher: `{ "message": "Teacher not found or user is not a teacher" }`
  - **422** — teacher is the group owner: `{ "message": "This teacher is already the group owner" }`
  - **409** — already an assistant: `{ "message": "Teacher is already an assistant in this group" }`
  - **422** — validation failed

---

## Remove Assistant Teacher

Removes an assistant teacher from the group. **Only the main (owner) teacher can call this.**

- **URL**: `/api/teacher/groups/{groupId}/assistant-teachers/{teacherId}`
- **Method**: `DELETE`
- **Auth Required**: Yes (must be the group owner)

- **Success Response** `200`:
  ```json
  {
    "message": "Assistant teacher removed successfully",
    "group": { ...group object... }
  }
  ```

- **Error Responses**:
  - **404** — group not found
  - **403** — you are not the group owner
  - **404** — teacher is not an assistant in this group: `{ "message": "Teacher is not an assistant in this group" }`

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
