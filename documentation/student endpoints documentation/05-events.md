# Events

Events are meetings, lessons, or other sessions the teacher/admin has created and invited the student to attend.

---

## List Events

`GET /api/student/events`

Returns all events the student is invited to — either directly (their ID appears in `guests`) or via group membership (any of their groups appears in `guest_groups`). Ordered by date and time. Includes past events.

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

Returns details of a single event. The student must be directly invited (`guests`) or a member of an invited group (`guest_groups`).

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

**Errors**: `404` if not found or the student has no access (not in `guests` and not in any invited group).
