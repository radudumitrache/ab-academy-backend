# Schedule

## Get Weekly Schedule

`GET /api/student/schedule`

Returns the recurring weekly schedule for all groups the student is enrolled in, plus upcoming events the student is invited to.

> **Timezone note** — `event_date` and `event_time` in the `events` array are returned in the student's timezone (set via `PUT /api/student/profile`, default `Europe/Bucharest`). `schedule_days` times (from the group's weekly schedule) are plain local-time strings and are **not** timezone-converted.

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
  ],
  "events": [
    {
      "id": 5,
      "title": "Grammar Review Session",
      "type": "meeting",
      "event_date": "2026-03-15",
      "event_time": "09:00:00",
      "event_duration": 90,
      "event_meet_link": "https://zoom.us/j/abc123",
      "event_notes": "Bring your textbook.",
      "organizer": {
        "id": 4,
        "username": "teacher_ana"
      }
    }
  ]
}
```

`events` contains upcoming events (today or later) where **any** of the following is true:
- The student's ID is in `guests` (direct invite), or
- Any of their current group IDs appears in `guest_groups` (explicit group invite), or
- The event organizer is the teacher of one of their current groups (backwards-compatible fallback — ensures students who join a group after events are scheduled still see them).

Group removal is respected immediately — leaving a group removes access to that group's teacher-based events. Ordered by date and time. Returns an empty array if none.

Each `schedule_days` entry:

| Field | Type | Description |
|-------|------|-------------|
| `day` | string | Day of the week (e.g. `"Monday"`) |
| `time` | string | Start time in `HH:MM` format |
| `duration` | integer | Session duration in minutes |

Each `events` entry:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Event ID |
| `title` | string | Event title |
| `type` | string | Event type (`class`, `meeting`, `other`) |
| `event_date` | date | Date of the event |
| `event_time` | time | Start time |
| `event_duration` | integer | Duration in minutes |
| `event_meet_link` | string\|null | Zoom or meeting join URL |
| `event_notes` | string\|null | Additional notes |
| `organizer` | object\|null | Organizer's `id` and `username` |
