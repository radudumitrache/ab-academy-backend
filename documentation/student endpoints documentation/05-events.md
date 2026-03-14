# Events

Events are meetings, lessons, or other sessions the teacher/admin has created and invited the student to attend.

---

## List Events

`GET /api/student/events?month={month}&year={year}`

Returns events for the given month/year that the student has access to — directly invited (`guests`) or via group invite (`guest_groups`). Ordered by date and time.

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `month` | integer | No | Month number (1–12). Defaults to the current month. |
| `year` | integer | No | Full year (e.g. `2026`). Defaults to the current year. |

**Errors**: `422` if `month` is not between 1–12 or `year` is out of the valid range.

**Response** `200`:
```json
{
  "message": "Events retrieved successfully",
  "count": 2,
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

---

## Get Single Event

`GET /api/student/events/{id}`

Returns details of a single event. Access follows the same rule as the list endpoint: direct invite or group invite.

**Response** `200`:
```json
{
  "message": "Event retrieved successfully",
  "event": {
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
}
```

**Errors**: `404` if not found or the student has no access.
