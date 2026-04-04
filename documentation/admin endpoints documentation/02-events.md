# Events

Admins have full access to **all events** in the database — no ownership filter applies. They can create, edit, delete, create Zoom meetings, schedule recurring events for any organizer, and view attendance for any event.

> **Timezone note** — `event_date` and `event_time` are always returned in the **requesting user's timezone** (set via `PUT /api/admin/profile`). When creating or updating events, submit `event_date` and `event_time` in your own timezone — the API converts them to UTC for storage. Both fields must be submitted together when changing the time. Users without a timezone set default to `Europe/Bucharest`.

---

## List All Events

`GET /api/admin/events?organizer_id={id}`

Returns every event ordered by date and time, with the organizer relation resolved.

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `organizer_id` | integer | No | Filter events by organizer (user ID). Omit to return all events. |

**Response** `200`:
```json
{
  "message": "Events retrieved successfully",
  "events": [
    {
      "id": 1,
      "title": "Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "guest_groups": [1, 2],
      "present_guests": [],
      "event_meet_link": "https://us05web.zoom.us/j/12345678?pwd=...",
      "event_start_link": "https://us05web.zoom.us/s/12345678?zak=...",
      "event_notes": "Bring your textbooks",
      "meeting_account_id": 1,
      "recurrence_parent_id": null,
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  ]
}
```

---

## Create Event

`POST /api/admin/events`

**Request body**:

| Field | Type | Required | Notes |
|---|---|---|---|
| `title` | string | yes | max 255 chars |
| `type` | string | yes | `class`, `meeting`, or `other` |
| `event_date` | date | yes | e.g. `2026-03-15` |
| `event_time` | string | yes | `HH:MM` (24-hour) |
| `event_duration` | integer | yes | minutes, min 1 |
| `event_organizer` | integer | yes | must be a user with role `teacher` or `admin` |
| `guests` | array of int | no | user IDs — each must exist in `users` |
| `guest_groups` | array of int | no | group IDs — each must exist in `groups` |
| `event_meet_link` | string (URL) | no | manual meeting link |
| `event_notes` | string | no | |

```json
{
  "title": "Grammar Workshop",
  "type": "class",
  "event_date": "2026-03-15",
  "event_time": "10:00",
  "event_duration": 90,
  "event_organizer": 2,
  "guests": [3, 4, 5],
  "guest_groups": [1],
  "event_notes": "Bring your textbooks"
}
```

**Response** `201`:
```json
{
  "message": "Event created successfully",
  "event": { ... }
}
```

---

## Get Event Details

`GET /api/admin/events/{id}`

**Response** `200`:
```json
{
  "message": "Event retrieved successfully",
  "event": {
    "id": 1,
    "title": "Grammar Workshop",
    "type": "class",
    "event_date": "2026-03-15",
    "event_time": "10:00:00",
    "event_duration": 90,
    "event_organizer": 2,
    "guests": [3, 4, 5],
    "guest_groups": [1],
    "present_guests": [],
    "event_meet_link": "https://us05web.zoom.us/j/12345678?pwd=...",
    "event_start_link": "https://us05web.zoom.us/s/12345678?zak=...",
    "event_notes": "Bring your textbooks",
    "meeting_account_id": 1,
    "recurrence_parent_id": null,
    "organizer": {
      "id": 2,
      "username": "teacher1",
      "role": "teacher"
    }
  }
}
```

**Errors**: `404` if not found.

---

## Update Event

`PUT /api/admin/events/{id}`

All fields are optional (`sometimes`). Only include the fields you want to change.

**Request body** (same fields as Create, all optional):
```json
{
  "title": "Updated Grammar Workshop",
  "guest_groups": [1, 2],
  "event_notes": "Updated notes"
}
```

**Response** `200`:
```json
{
  "message": "Event updated successfully",
  "event": { ... }
}
```

**Errors**: `404` if not found.

---

## Delete Event

`DELETE /api/admin/events/{id}`

**Response** `200`:
```json
{
  "message": "Event deleted successfully"
}
```

**Errors**: `404` if not found.

---

## Create Zoom Meeting

`POST /api/admin/events/{id}/create-zoom-meeting`

Iterates all active meeting accounts and checks the **Zoom API directly** for each one to find the first account that has no meeting overlapping the event's time window. Creates a scheduled Zoom meeting on that account. Stores the guest join URL in `event_meet_link`, the host start URL in `event_start_link`, and records the chosen account in `meeting_account_id`. The meeting `start_time` is sent to Zoom in UTC with `timezone: UTC`.

**Body**: none

**Response** `200`:
```json
{
  "message": "Zoom meeting created successfully",
  "event": {
    "id": 1,
    "event_meet_link": "https://us05web.zoom.us/j/12345678?pwd=...",
    "event_start_link": "https://us05web.zoom.us/s/12345678?zak=...",
    "meeting_account_id": 1,
    ...
  },
  "meeting_link": "https://us05web.zoom.us/j/12345678?pwd=...",
  "start_link": "https://us05web.zoom.us/s/12345678?zak=..."
}
```

**Errors**:
- `404` — event not found
- `422` — no active meeting accounts configured
- `422` — all meeting accounts are busy during this time slot (checked live against Zoom API)
- `502` — Zoom API call failed (message included)

**Account selection**: every active meeting account is queried via the Zoom API. The first account that has no scheduled meeting overlapping the event's date/time window is used. Accounts that are unreachable are silently skipped.

---

## Schedule Recurring Events for the Month

`POST /api/admin/events/{id}/recur-monthly`

Creates weekly copies of the event for every remaining occurrence within the **same calendar month** as the source event's date. The source event itself is not modified.

**Request body** (all optional):

| Field | Type | Default | Notes |
|---|---|---|---|
| `interval_weeks` | integer (1–4) | `1` | weeks between each copy |
| `create_zoom` | boolean | `false` | auto-create a Zoom meeting for each copy |

```json
{
  "interval_weeks": 1,
  "create_zoom": true
}
```

**Response** `201`:
```json
{
  "message": "3 recurring event(s) created",
  "events": [
    {
      "id": 12,
      "title": "Grammar Workshop",
      "event_date": "2026-03-22",
      "recurrence_parent_id": 1,
      "event_meet_link": "https://us05web.zoom.us/j/...",
      ...
    },
    {
      "id": 13,
      "title": "Grammar Workshop",
      "event_date": "2026-03-29",
      "recurrence_parent_id": 1,
      ...
    }
  ]
}
```

**Response** `200` (no copies fit in the month):
```json
{
  "message": "No additional occurrences fit within the current month",
  "events": []
}
```

**Errors**: `404` if the source event is not found.

**Notes**:
- Each copy inherits `title`, `type`, `event_time`, `event_duration`, `event_organizer`, `guests`, `guest_groups`, and `event_notes` from the source.
- `recurrence_parent_id` on each copy points to the **original** event (not a copy-of-a-copy chain). If you recur an event that is itself already a copy, all new events will share the same `recurrence_parent_id`.
- When `create_zoom` is `true` and no meeting account is available for a given date/time, that copy is still created — its `event_meet_link` will be `null` (Zoom failure does not abort the batch).


---

## Get Event Attendance

Returns all guests for an event (direct invites + all members of invited groups), each with their recorded attendance status. Guests not yet marked have `status: null`.

`GET /api/admin/events/{id}/attendance`

**Response** `200`:
```json
{
  "message": "Attendance retrieved successfully",
  "event_id": 5,
  "attendance": [
    { "student_id": 12, "username": "student1", "email": "s1@example.com", "role": "student", "status": "present" },
    { "student_id": 15, "username": "student2", "email": "s2@example.com", "role": "student", "status": null },
    { "student_id": 19, "username": "teacher2", "email": "t2@example.com", "role": "teacher", "status": "absent" }
  ]
}
```

**Errors**: `404` if event not found.
