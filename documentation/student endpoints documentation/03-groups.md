# Groups

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
    "description": "Advanced grammar and conversation.",
    "class_code": "AB12CD34",
    "schedule_days": [
      { "day": "Monday", "time": "09:00", "duration": 90 },
      { "day": "Wednesday", "time": "09:00", "duration": 90 }
    ]
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
