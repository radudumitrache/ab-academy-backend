# Groups (Student)

Students interact with groups by joining via a class code and viewing their course hour statistics.

---

## Get Group Detail

Returns details for a single group the student belongs to, including assigned homework (only published homework is returned — drafts are excluded).

- **URL**: `/api/student/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Group retrieved successfully",
    "group": {
      "group_id": 3,
      "group_name": "English B2 — Morning",
      "description": "Morning B2 class",
      "schedule_days": [
        { "day": "Monday", "time": "09:00", "duration": 120 }
      ],
      "formatted_schedule": "Monday at 09:00 (120min)",
      "teacher": { "id": 5, "username": "Teacher Name" },
      "homework": [
        {
          "id": 12,
          "homework_title": "Unit 3 Exercise",
          "homework_description": "Complete exercises 1–5",
          "due_date": "2026-04-10",
          "submission_status": "not_started",
          "submitted_at": null
        }
      ]
    }
  }
  ```
- **Notes**:
  - Only homework with `status = posted` is included. Draft homework is never visible to students.
  - `submission_status` values: `not_started`, `draft`, `submitted`, `graded`
- **Error Responses**:
  - `404` — group not found or student is not a member

---

## Join a Group

Join a group using the class code provided by the teacher.

- **URL**: `/api/student/groups/join`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "class_code": "ABC123" }
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Joined group successfully",
    "group": {
      "group_id": 3,
      "group_name": "English B2 — Morning",
      "class_code": "ABC123",
      "students": [ { ... } ]
    }
  }
  ```
- **Error Responses**:
  - `404` — invalid class code
  - `409` — student is already in this group
  - `422` — `class_code` missing

---

## Course Hours

Returns a breakdown of the student's attendance and scheduled course hours across all their groups.

- **URL**: `/api/student/groups/hours`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Course hours retrieved successfully",
    "total_minutes_scheduled": 480,
    "total_minutes_attended": 360,
    "groups": [
      {
        "group_id": 3,
        "group_name": "English B2 — Morning",
        "weekly_minutes": 120,
        "total_sessions_held": 4,
        "sessions_present": 3,
        "sessions_absent": 1,
        "sessions_motivated_absent": 0,
        "total_minutes_scheduled": 480,
        "minutes_attended": 360
      }
    ]
  }
  ```

| Field | Description |
|-------|-------------|
| `total_sessions_held` | Sessions with at least one attendance record |
| `sessions_present` | Sessions where the student was marked present |
| `sessions_absent` | Sessions where the student was marked absent |
| `sessions_motivated_absent` | Sessions with a motivated/excused absence |
| `total_minutes_scheduled` | Sum of session durations for all held sessions |
| `minutes_attended` | Minutes from sessions the student was present |
