# Group Management

Admins have full access to **all groups** — no ownership filter applies. They can create, edit, delete, and manage members of any group, regardless of which teacher owns it.

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
  "teacher": {
    "id": 4,
    "username": "teacher_ana",
    "role": "teacher"
  },
  "students": [
    { "id": 12, "username": "student1", "role": "student" },
    { "id": 15, "username": "student2", "role": "student" }
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
|---|---|
| `group_id` | Unique group identifier |
| `group_name` | Display name of the group |
| `group_teacher` | User ID of the owning teacher |
| `description` | Optional free-text description |
| `class_code` | 8-character alphanumeric code (`null` until generated) |
| `schedule_days` | Array of `{ day, time, duration }` objects — one per session slot |
| `schedule_days[].day` | Day of the week (e.g. `Monday`) |
| `schedule_days[].time` | Start time in `HH:MM` 24-hour format |
| `schedule_days[].duration` | Session length in minutes |
| `formatted_schedule` | Human-readable schedule string |
| `total_weekly_minutes` | Sum of all session durations per week |
| `group_members` | Array of student user IDs currently in the group |
| `assistant_teacher_ids` | Array of assistant teacher user IDs |
| `teacher` | Resolved owner teacher object |
| `students` | Full student objects (eager-loaded) |
| `assistant_teachers` | Full assistant teacher objects (eager-loaded) |

---

## Get Schedule Options

`GET /api/admin/groups/schedule/options`

Returns allowed values for `schedule_days[].day` and `schedule_days[].time`.

**Response** `200`:
```json
{
  "message": "Schedule options retrieved successfully",
  "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
  "times": ["08:00", "08:30", "09:00", "09:30", "...", "20:00", "20:30"]
}
```

---

## List All Groups

`GET /api/admin/groups`

Returns all groups (all teachers) with teacher and students eager-loaded.

**Response** `200`:
```json
{
  "message": "Groups retrieved successfully",
  "groups": [ { ...group object... } ]
}
```

---

## Get Single Group

`GET /api/admin/groups/{id}`

**Response** `200`:
```json
{
  "message": "Group retrieved successfully",
  "group": { ...group object... }
}
```

**Errors**: `404` if not found.

---

## Create Group

`POST /api/admin/groups`

Unlike teachers (who are auto-assigned as organizer), admins must explicitly provide `group_teacher`.

**Request body**:

| Field | Type | Required | Notes |
|---|---|---|---|
| `group_name` | string | yes | max 255 chars |
| `group_teacher` | integer | yes | must be a valid user ID |
| `description` | string | no | |
| `schedule_days` | array | yes | at least one entry |
| `schedule_days[].day` | string | yes | must be a valid day (see Schedule Options) |
| `schedule_days[].time` | string | yes | `HH:MM` 24-hour format |
| `schedule_days[].duration` | integer | yes | session length in minutes, min 1 |
| `group_members` | array of int | no | student user IDs |

```json
{
  "group_name": "English A1",
  "group_teacher": 4,
  "description": "Beginner English group.",
  "schedule_days": [
    { "day": "Monday",    "time": "18:00", "duration": 90 },
    { "day": "Wednesday", "time": "18:00", "duration": 90 }
  ],
  "group_members": [12, 15, 19]
}
```

**Response** `201`:
```json
{
  "message": "Group created successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `422` — validation failed (missing fields, invalid day/time, invalid duration)
- `422` — one or more `group_members` IDs are not valid students: `{ "message": "All group_members must be valid students" }`

---

## Update Group

`PUT /api/admin/groups/{id}`

All fields are optional. Providing `schedule_days` replaces the entire schedule. Providing `group_members` syncs (replaces) the full student list. Admins can reassign a group to a different teacher via `group_teacher`.

**Request body** (all fields optional):
```json
{
  "group_name": "English A1 — Advanced",
  "group_teacher": 5,
  "schedule_days": [
    { "day": "Tuesday", "time": "19:00", "duration": 60 }
  ],
  "group_members": [12, 15]
}
```

**Response** `200`:
```json
{
  "message": "Group updated successfully",
  "group": { ...group object... }
}
```

**Errors**: `404` if not found, `422` if validation fails or members are invalid.

---

## Delete Group

`DELETE /api/admin/groups/{id}`

Soft-deletes the group. Data is retained and can be restored via the archive endpoints.

**Response** `200`:
```json
{ "message": "Group deleted successfully" }
```

**Errors**: `404` if not found.

---

## Add Student to Group

`POST /api/admin/groups/{id}/students`

**Request body**:
```json
{ "student_id": 12 }
```

**Response** `200`:
```json
{
  "message": "Student added to group successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `404` — student_id exists but is not a student: `{ "message": "Student not found or user is not a student" }`
- `409` — student already in group: `{ "message": "Student is already in this group" }`
- `422` — validation failed

---

## Add Student to Group by Username

`POST /api/admin/groups/{id}/students/by-username`

Same behaviour as above but looks up the student by `username`.

**Request body**:
```json
{ "username": "student1" }
```

**Response** `200`:
```json
{
  "message": "Student added to group successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `404` — username not found or belongs to a non-student: `{ "message": "Student not found or user is not a student" }`
- `409` — student already in group
- `422` — validation failed

---

## Remove Student from Group

`DELETE /api/admin/groups/{groupId}/students/{studentId}`

**Response** `200`:
```json
{
  "message": "Student removed from group successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `404` — student is not in this group: `{ "message": "Student is not in this group" }`

---

## Update Group Members (Bulk Replace)

`PUT /api/admin/groups/{id}/members`

Replaces the entire student list with the provided IDs (sync). Pass an empty array to remove all students.

**Request body**:
```json
{ "group_members": [3, 5, 7, 8] }
```

**Response** `200`:
```json
{
  "message": "Group members updated successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `422` — one or more IDs are not valid students

---

## Generate Class Code

`POST /api/admin/groups/{id}/generate-code`

Generates (or regenerates) a unique 8-character alphanumeric class code for the group. Students use this code to self-enroll. Calling this again replaces the existing code.

**Body**: none

**Response** `200`:
```json
{
  "message": "Class code generated successfully",
  "class_code": "AB12CD34"
}
```

**Errors**: `404` if group not found.

---

## Add Assistant Teacher

`POST /api/admin/groups/{id}/assistant-teachers`

Assigns a teacher as an assistant to the group. Admins can assign any teacher regardless of group ownership.

**Request body**:
```json
{ "teacher_id": 7 }
```

**Response** `200`:
```json
{
  "message": "Assistant teacher added successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `404` — `teacher_id` is not a valid teacher: `{ "message": "Teacher not found or user is not a teacher" }`
- `422` — teacher is already the group owner: `{ "message": "This teacher is already the group owner" }`
- `409` — already an assistant: `{ "message": "Teacher is already an assistant in this group" }`
- `422` — validation failed

---

## Remove Assistant Teacher

`DELETE /api/admin/groups/{groupId}/assistant-teachers/{teacherId}`

**Response** `200`:
```json
{
  "message": "Assistant teacher removed successfully",
  "group": { ...group object... }
}
```

**Errors**:
- `404` — group not found
- `404` — teacher is not an assistant: `{ "message": "Teacher is not an assistant in this group" }`

---

## Schedule Reference

### Available Days
`Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `Sunday`

### Available Times
`08:00` to `20:30` in 30-minute increments. Use `GET /api/admin/groups/schedule/options` to retrieve the full list programmatically.

---

## Get Group Attendance

Returns all recorded attendance for a group, ordered by session date and time. Optionally filter to a single session date.

`GET /api/admin/groups/{id}/attendance`

**Query Parameters**:

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| `session_date` | string | No | Filter to a single session (`YYYY-MM-DD`) |

**Response** `200`:
```json
{
  "message": "Attendance retrieved successfully",
  "group_id": 3,
  "group_name": "English B2 Morning",
  "attendance": [
    { "student_id": 12, "username": "student1", "email": "s1@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "present" },
    { "student_id": 15, "username": "student2", "email": "s2@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "absent" }
  ]
}
```

**Errors**: `404` if group not found.
