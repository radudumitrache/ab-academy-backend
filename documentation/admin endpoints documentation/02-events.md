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
| `event_duration` | integer | yes | minutes, min 1, max 240 (4h — see `ZOOM_MAX_MEETING_MINUTES`) |
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

If the event has a Zoom meeting and the edit changed `title`, `event_date`, `event_time` or `event_duration`, the Zoom meeting is rescheduled to match. If that call fails the edit is still saved and a `zoom_warning` string is added to the response; until the edit is retried successfully the meeting link points at the old slot, and the old slot stays blocked on that account.

**Errors**: `404` if not found.

---

## Delete Event

`DELETE /api/admin/events/{id}`

Cancels the event's Zoom meeting first, if it has one, so the time slot returns to the pool.

**Response** `200`:
```json
{
  "message": "Event deleted successfully"
}
```

If the Zoom meeting could not be cancelled the event is still deleted and a `zoom_warning` string is added to the response. An orphaned Zoom meeting keeps occupying its account for that window and has to be removed from the Zoom side.

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
- `502` — one or more accounts could not be reached, so availability is unknown — retry

A failure response also includes `busy_accounts`, `error_accounts` and the `checked_window` that was tested, so the reason is inspectable.

**Account selection**: every active meeting account is queried via the Zoom API. The first account with no meeting overlapping the event's date/time window is used.

A meeting already in progress counts against an account only when it actually overlaps the requested window — including when it has run past its scheduled end, in which case it holds the account until it really finishes. A meeting running right now does not block a slot next week.

Zoom's live list also returns Personal Meeting Rooms and instant meetings with neither a `start_time` nor a `duration` — usually rooms someone opened and never ended. They still hold the host (Zoom will not start another meeting on that account), but describe no window, so they are assumed to occupy the account for `ZOOM_LIVE_UNKNOWN_WINDOW_MINUTES` (default `30`) from now. They block events about to start, not later ones.

A scheduled meeting that began more than `ZOOM_NO_SHOW_GRACE_MINUTES` (default `15`) ago and is not live has ended or never started, and stops blocking its account; Zoom's scheduled list keeps such meetings for a month and does not say whether they ran.

**Idempotent**: if the event already has a Zoom meeting that still exists on Zoom, that meeting is returned (message `"This event already has a Zoom meeting"`) instead of booking a second one. A new meeting is booked only when Zoom reports the old one as gone. Bookings are serialised, so two simultaneous requests cannot take the same account for the same slot; a request that waits too long for its turn gets a `502` and can be retried.

Each entry in `busy_accounts` carries a `conflict_source` of `scheduled` or `live`, saying which check flagged it.

Unreachable accounts are **not** silently skipped. Busy accounts land in `busy_accounts` and unreachable ones in `error_accounts`, and the status code says which happened: `422` means the pool is genuinely full, `502` means the answer is inconclusive and the request should be retried.

**Meeting length cap**: no meeting may exceed `ZOOM_MAX_MEETING_MINUTES` (default `240`, i.e. 4 hours). The limit is enforced in three places: `event_duration` is rejected by validation above it, the duration sent to Zoom is clamped to it (covering events stored before the limit existed), and an existing meeting counts as occupying its account for at most that long. That last point matters because Zoom stores whatever duration it is given, and some accounts hold meetings created with placeholder lengths of 9999, 99999 and 999999 minutes (7, 69 and 694 days); taken literally, one stale meeting blocked its account for years. Meetings at or under the cap are unaffected, every clamp is logged as a warning, and `busy_accounts` reports both the raw `conflicting_duration_min` and the `effective_duration_min` actually used.

**Changeover buffer**: an account needs `ZOOM_MEETING_BUFFER_MINUTES` (default `20`) of clearance after a meeting ends before hosting the next. It applies only *after* an existing meeting. Any non-zero value prevents back-to-back bookings on one account, so set it to `0` if lessons run on the hour.

---

## Get Zoom Start Link

`GET /api/admin/events/{id}/zoom-start-link`

Fetches a freshly issued host start URL from Zoom and refreshes the stored `event_start_link`. The `start_url` captured when a meeting is created embeds a token that expires about 2 hours later, so for anything booked further ahead the stored value is stale by class time. Call this at the moment the host starts the meeting rather than using `event_start_link` directly.

**Body**: none

**Response** `200`:
```json
{
  "message": "Start link retrieved successfully",
  "start_link": "https://us05web.zoom.us/s/12345678?zak=...",
  "meeting_link": "https://us05web.zoom.us/j/12345678?pwd=..."
}
```

When the meeting's Zoom account is already in another meeting, Zoom will refuse to start this one. The response then also includes `start_warning` (text) and `blocking_meeting` (`{ "id", "topic" }`); both are absent when nothing is blocking.

**Errors**:
- `404` — event not found
- `422` — this event has no Zoom meeting to start
- `502` — Zoom could not be reached (message included)

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
