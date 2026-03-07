# Schedule

## Get Weekly Schedule

`GET /api/student/schedule`

Returns the schedule for all groups the student is enrolled in. Each group lists its recurring weekly session slots.

**Response** `200`:
```json
{
  "message": "Schedule retrieved successfully",
  "schedule": [
    {
      "group_id": 3,
      "group_name": "English B2 — Morning",
      "description": "Advanced grammar and conversation.",
      "teacher": {
        "id": 4,
        "username": "teacher_ana"
      },
      "schedule_days": [
        { "day": "Monday",    "time": "09:00", "duration": 90 },
        { "day": "Wednesday", "time": "09:00", "duration": 90 }
      ],
      "formatted_schedule": "Monday at 09:00 (90min), Wednesday at 09:00 (90min)",
      "total_weekly_minutes": 180
    }
  ]
}
```

Each `schedule_days` entry:

| Field | Type | Description |
|-------|------|-------------|
| `day` | string | Day of the week (e.g. `"Monday"`) |
| `time` | string | Start time in `HH:MM` format |
| `duration` | integer | Session duration in minutes |
