# Events

Events are meetings, lessons, or other sessions the teacher/admin has created and invited the student to attend.

---

## List Events

`GET /api/student/events`

Returns all events where the authenticated student appears in the `guests` list, ordered by date and time.

**Response** `200`:
```json
{
  "message": "Events retrieved successfully",
  "count": 2,
  "events": [
    {
      "id": 5,
      "title": "Grammar Review Session",
      "type": "lesson",
      "event_date": "2026-03-10",
      "event_time": "09:00:00",
      "event_duration": 90,
      "event_organizer": 4,
      "guests": [12, 15, 18],
      "present_guests": [],
      "event_meet_link": "https://meet.google.com/abc-def-ghi",
      "event_notes": "Bring your textbook.",
      "created_at": "2026-03-07T10:00:00.000000Z",
      "updated_at": "2026-03-07T10:00:00.000000Z"
    }
  ]
}
```

---

## Get Single Event

`GET /api/student/events/{id}`

Returns details of a single event. The student must be listed in `guests`.

**Response** `200`:
```json
{
  "message": "Event retrieved successfully",
  "event": { ... }
}
```

**Errors**: `404` if not found or student is not a guest.
