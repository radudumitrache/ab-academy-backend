# Groups (Student)

```
GET  /api/student/groups           → list all groups the student belongs to
GET  /api/student/groups/{id}      → group detail with assigned homework
POST /api/student/groups/join      → join a group via class code
GET  /api/student/groups/hours     → attendance & course hours summary
```

---

## List Groups

`GET /api/student/groups`

Returns all groups the student is a member of.

**Response** `200`:
```json
{
  "message": "Groups retrieved successfully",
  "count": 1,
  "groups": [
    {
      "group_id": 3,
      "group_name": "English B2 — Morning",
      "description": "Advanced grammar and conversation.",
      "schedule_days": [
        { "day": "Monday", "time": "09:00", "duration": 90 },
        { "day": "Wednesday", "time": "09:00", "duration": 90 }
      ],
      "formatted_schedule": "Monday at 09:00 (90min), Wednesday at 09:00 (90min)",
      "teacher": { "id": 5, "username": "teacher_ana" }
    }
  ]
}
```

---

## Get Group Detail (with homework)

`GET /api/student/groups/{id}`

Returns group details plus all homework assigned to this group, with the student's submission status for each.

**Response** `200`:
```json
{
  "message": "Group retrieved successfully",
  "group": {
    "group_id": 3,
    "group_name": "English B2 — Morning",
    "description": "Advanced grammar and conversation.",
    "schedule_days": [
      { "day": "Monday", "time": "09:00", "duration": 90 },
      { "day": "Wednesday", "time": "09:00", "duration": 90 }
    ],
    "formatted_schedule": "Monday at 09:00 (90min), Wednesday at 09:00 (90min)",
    "teacher": { "id": 5, "username": "teacher_ana" },
    "homework": [
      {
        "id": 3,
        "homework_title": "TEST HOMEWORK",
        "homework_description": "TO DO DO TO",
        "due_date": "2026-12-25",
        "submission_status": "not_started",
        "submitted_at": null
      },
      {
        "id": 5,
        "homework_title": "Unit 5 Practice",
        "homework_description": "Complete all sections.",
        "due_date": "2026-03-20",
        "submission_status": "submitted",
        "submitted_at": "2026-03-18T14:00:00.000000Z"
      }
    ]
  }
}
```

**Errors**: `404` if the group doesn't exist or the student is not a member.

---

## Join a Group

`POST /api/student/groups/join`

Join a group using the 8-character class code provided by the teacher.

```json
{ "class_code": "AB12CD34" }
```

**Response** `200`:
```json
{
  "message": "Joined group successfully",
  "group": {
    "group_id": 3,
    "group_name": "English B2 — Morning",
    "class_code": "AB12CD34"
  }
}
```

**Errors**:
- `404` — invalid class code
- `409` — student is already in this group

---

## Course Hours & Attendance

`GET /api/student/groups/hours`

Returns attendance statistics and scheduled minutes for each group the student belongs to.

**Response** `200`:
```json
{
  "message": "Course hours retrieved successfully",
  "total_minutes_scheduled": 540,
  "total_minutes_attended": 450,
  "groups": [
    {
      "group_id": 3,
      "group_name": "English B2 — Morning",
      "weekly_minutes": 180,
      "total_sessions_held": 6,
      "sessions_present": 5,
      "sessions_absent": 1,
      "sessions_motivated_absent": 0,
      "total_minutes_scheduled": 540,
      "minutes_attended": 450
    }
  ]
}
```
