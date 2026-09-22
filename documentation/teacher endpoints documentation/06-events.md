# Event Management

Teachers can view events they are involved in (as organizer, guest, or assistant teacher of an invited group), create new events with themselves as organizer, and edit or delete events they are authorized to manage.

> **Timezone note** — `event_date` and `event_time` are always returned in the **requesting user's timezone** (set via `PUT /api/teacher/profile`). When creating or updating events, submit `event_date` and `event_time` in your own timezone — the API converts them to UTC for storage. Both fields must be submitted together when changing the time. Users without a timezone set default to `Europe/Bucharest`.

**Management access** applies to the organizer **and** to any teacher who is an assistant of a group listed in the event's `guest_groups`. Managers can edit, delete, mark attendance, add guests, and create Zoom meetings. The `event_start_link` (host URL) is visible only to managers.

Plain guests (directly invited but not a manager) can view events but cannot modify them and do not receive `event_start_link`.

---

## Event Object

```json
{
  "id": 5,
  "title": "Parent-Teacher Meeting",
  "type": "meeting",
  "event_date": "2026-03-10",
  "event_time": "14:00:00",
  "event_duration": 60,
  "event_organizer": 4,
  "guests": [12, 15, 19],
  "guest_groups": [2, 5],
  "present_guests": null,
  "event_meet_link": "https://us05web.zoom.us/j/12345678?pwd=...",
  "event_start_link": "https://us05web.zoom.us/s/12345678?zak=...",
  "event_notes": "Bring attendance records.",
  "meeting_account_id": 1,
  "organizer": {
    "id": 4,
    "username": "teacher1",
    "role": "teacher"
  },
  "created_at": "2026-02-20T10:00:00.000000Z",
  "updated_at": "2026-02-20T10:00:00.000000Z"
}
```

| Field | Description |
|-------|-------------|
| `id` | Unique event identifier |
| `title` | Event title |
| `type` | `class`, `meeting`, or `other` |
| `event_date` | Date of the event (`YYYY-MM-DD`) — in the requesting user's timezone |
| `event_time` | Start time (`HH:MM`) — in the requesting user's timezone |
| `event_duration` | Duration in minutes |
| `event_organizer` | User ID of the organizer |
| `guests` | Array of individual user IDs invited directly |
| `guest_groups` | Array of group IDs — all members of these groups can see the event |
| `present_guests` | Array of guest IDs who attended (managed separately) |
| `event_meet_link` | Guest join URL (populated after Zoom meeting creation) |
| `event_start_link` | Host start URL — visible only to managers (organizer or assistant teachers of invited groups). Opens meeting as the Zoom host. Contains an embedded token that expires ~2 hours after creation. |
| `event_notes` | Optional free-text notes |
| `meeting_account_id` | ID of the `MeetingAccount` used to create the Zoom meeting |
| `organizer` | Full organizer object (eager-loaded) |

---

## List My Events

Returns all events where the authenticated teacher is the organizer, appears in the guest list, **or** is an assistant teacher of any group in `guest_groups`.
Results are ordered by `event_date` and `event_time` ascending.
`event_start_link` is included only for events the teacher can manage (organizer or assistant of an invited group); it is omitted for plain guests.

- **URL**: `/api/teacher/events`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Events retrieved successfully",
    "events": [
      {
        "id": 5,
        "title": "Parent-Teacher Meeting",
        "type": "meeting",
        "event_date": "2026-03-10",
        "event_time": "14:00:00",
        "event_duration": 60,
        "event_organizer": 4,
        "guests": [12, 15],
        "organizer": { ... }
      }
    ]
  }
  ```

---

## Get Single Event

Returns a single event. Accessible to any authenticated teacher.

- **URL**: `/api/teacher/events/{id}`
- **Method**: `GET`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Event retrieved successfully",
    "event": { ... },
    "guest_users": [
      { "id": 12, "username": "student1", "email": "student1@example.com", "role": "student" },
      { "id": 15, "username": "teacher2", "email": "teacher2@example.com", "role": "teacher" }
    ]
  }
  ```

  > `guest_users` is the resolved list of full user objects for each ID in `guests`. The list endpoint returns raw guest IDs only; `show` resolves them to full objects.
  > `event_start_link` is included only if the requesting teacher is a manager (organizer or assistant of an invited group); otherwise it is hidden.

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```

---

## Create Event

Creates a new event. The authenticated teacher is automatically set as the organizer.

- **URL**: `/api/teacher/events`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "title": "Parent-Teacher Meeting",
    "type": "meeting",
    "event_date": "2026-03-10",
    "event_time": "14:00",
    "event_duration": 60,
    "guests": [12, 15, 19],
    "guest_groups": [2, 5],
    "event_meet_link": "https://meet.google.com/abc-defg-hij",
    "event_notes": "Bring attendance records."
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `title` | string | Yes | Max 255 characters |
  | `type` | string | Yes | `class`, `meeting`, or `other` |
  | `event_date` | string | Yes | Any valid date (e.g. `2026-03-10`) |
  | `event_time` | string | Yes | `HH:MM` format |
  | `event_duration` | integer | Yes | Duration in minutes, minimum 1 |
  | `guests` | array | No | Individual user IDs to invite directly |
  | `guest_groups` | array | No | Group IDs — all members of each group can see and join the event |
  | `event_meet_link` | string | No | Valid URL, max 2048 characters |
  | `event_notes` | string | No | Free-text notes |

- **Note**: `event_organizer` is always set to the authenticated teacher — it cannot be overridden.

- **Success Response** `201`:
  ```json
  {
    "message": "Event created successfully",
    "event": { ... }
  }
  ```

- **Error Responses**:
  - **422** — validation failed:
    ```json
    {
      "message": "The given data was invalid.",
      "errors": {
        "title": ["The title field is required."],
        "type": ["The selected type is invalid."],
        "event_time": ["The event time does not match the format H:i."]
      }
    }
    ```

---

## Update Event

Updates an event. The organizer **or** any assistant teacher of a group in the event's `guest_groups` may edit it.
All fields are optional — only provided fields are changed.

- **URL**: `/api/teacher/events/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body** (all fields optional):
  ```json
  {
    "title": "Updated Meeting Title",
    "event_date": "2026-03-12",
    "guests": [12, 15, 20],
    "guest_groups": [2, 5]
  }
  ```

- **Success Response** `200`:
  ```json
  {
    "message": "Event updated successfully",
    "event": { ... }
  }
  ```

  If the event has a Zoom meeting and the edit changed `title`, `event_date`, `event_time` or `event_duration`, the Zoom meeting is rescheduled to match. Should that call fail, the edit is still saved and the response carries an extra field:
  ```json
  {
    "message": "Event updated successfully",
    "event": { ... },
    "zoom_warning": "The event was saved, but its Zoom meeting could not be updated and is still scheduled at the previous time: ..."
  }
  ```
  Treat `zoom_warning` as a prompt to retry the edit — until it succeeds the meeting link points at the old slot, and the old slot stays blocked on that account.

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not a manager of this event:
    ```json
    { "message": "Unauthorized — only the event organizer or an assistant teacher of the event's groups can edit this event" }
    ```
  - **422** — validation failed (same shape as create).

---

## Delete Event

Deletes an event. The organizer **or** any assistant teacher of a group in the event's `guest_groups` may delete it. If the event has a Zoom meeting, that meeting is cancelled on Zoom first so its time slot returns to the pool.

- **URL**: `/api/teacher/events/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  { "message": "Event deleted successfully" }
  ```

  If the Zoom meeting could not be cancelled the event is still deleted, and the response carries a warning:
  ```json
  {
    "message": "Event deleted successfully",
    "zoom_warning": "The event was deleted, but its Zoom meeting could not be cancelled and may still block that time slot: ..."
  }
  ```
  An orphaned Zoom meeting keeps occupying its account for that window, so surface this to an admin — it can be removed from the Zoom side.

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not a manager of this event:
    ```json
    { "message": "Unauthorized — only the event organizer or an assistant teacher of the event's groups can delete this event" }
    ```

---

## Add Guests by Username

Adds one or more users to the event's guest list by their usernames.
Existing guests are preserved — duplicates are silently ignored.
The organizer **or** any assistant teacher of a group in the event's `guest_groups` may call this endpoint.

- **URL**: `/api/teacher/events/{id}/guests/by-username`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "usernames": ["student1", "teacher2"] }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `usernames` | array | Yes | One or more usernames to add as guests |

- **Success Response** `200`:
  ```json
  {
    "message": "Guests added successfully",
    "guests": [12, 15, 19],
    "guest_users": [
      { "id": 12, "username": "student1", "email": "...", "role": "student" },
      { "id": 19, "username": "teacher2", "email": "...", "role": "teacher" }
    ]
  }
  ```

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not a manager of this event:
    ```json
    { "message": "Unauthorized — only the event organizer or an assistant teacher of the event's groups can add guests" }
    ```
  - **422** — one or more usernames not found:
    ```json
    {
      "message": "Some usernames were not found",
      "unknown_usernames": ["nonexistent_user"]
    }
    ```

---

## Create Zoom Meeting

Iterates all active meeting accounts and checks the **Zoom API directly** for each one to find the first account that has no meeting overlapping the event's time window. Creates a scheduled Zoom meeting on that account. The organizer **or** any assistant teacher of a group in the event's `guest_groups` may call this. Stores the guest join URL in `event_meet_link`, the host start URL in `event_start_link`, and associates the chosen account via `meeting_account_id`.

- **URL**: `POST /api/teacher/events/{id}/create-zoom-meeting`
- **Auth Required**: Yes
- **Body**: none
- **Success Response** `200`:
  ```json
  {
    "message": "Zoom meeting created successfully",
    "event": {
      "id": 1,
      "title": "Math Class",
      "event_meet_link": "https://us05web.zoom.us/j/12345678?pwd=...",
      "event_start_link": "https://us05web.zoom.us/s/12345678?zak=...",
      "meeting_account_id": 1,
      ...
    },
    "meeting_link": "https://us05web.zoom.us/j/12345678?pwd=...",
    "start_link": "https://us05web.zoom.us/s/12345678?zak=..."
  }
  ```
- **Error Responses**:
  - `403` — teacher is not a manager of this event (not organizer and not assistant of any invited group)
  - `404` — event not found
  - `422` — no active meeting accounts configured
  - `422` — all meeting accounts are busy during this time slot (checked live against Zoom API)
  - `502` — one or more accounts could not be reached, so availability is unknown — retry

  A failure response also carries the detail behind the verdict:
  ```json
  {
    "message": "All meeting accounts are busy during this time slot",
    "busy_accounts": [
      {
        "account_id": 1,
        "account_name": "Main",
        "conflicting_topic": "English B2",
        "conflicting_start": "2026-10-01T10:00:00Z",
        "conflicting_duration_min": 60
      }
    ],
    "error_accounts": [],
    "checked_window": {
      "start_utc": "2026-10-01T10:30:00+00:00",
      "end_utc": "2026-10-01T11:30:00+00:00"
    }
  }
  ```

**How account selection works**: Every active meeting account is queried via the Zoom API. The first account with no meeting overlapping the event's time window is used.

A meeting already in progress only counts against an account when it actually overlaps the requested window — including when it has run past its scheduled end, in which case it holds the account until it really finishes. A meeting running right now therefore does not block a slot next week.

Accounts are **not** silently skipped when unreachable. Busy accounts land in `busy_accounts` and unreachable ones in `error_accounts`, and the status code reflects which happened: `422` means the pool is genuinely full, so pick another slot; `502` means at least one account could not be reached and the answer is inconclusive, so retry.

**Changeover buffer**: an account needs `ZOOM_MEETING_BUFFER_MINUTES` (default `20`) of clearance after one meeting ends before it may host the next. The buffer applies only *after* an existing meeting, so an event may end exactly when the following one begins. Note that any non-zero value prevents back-to-back bookings on a single account — with the default of 20, a 10:00–11:00 meeting blocks an 11:00 start. Set it to `0` if lessons are scheduled on the hour.

**Host vs guest URLs**:
- `meeting_link` / `event_meet_link` — the guest join URL. Share this with students and other attendees.
- `start_link` / `event_start_link` — the host start URL. Show this **only to managers** (organizer or assistant teachers of invited groups). Opening it launches the meeting with the organizer as the Zoom host (owner). Its embedded token expires roughly 2 hours after issue, so for anything booked further ahead the stored value is stale by class time — fetch a fresh one from **Get Zoom Start Link** below rather than using `event_start_link` directly.

---

## Get Zoom Start Link

Fetches a freshly issued host start URL for the event's Zoom meeting, and refreshes the stored `event_start_link` with it. Call this when a manager actually starts the meeting: the link captured at creation time contains a token that expires about 2 hours later, so it is almost always dead by the time a scheduled class begins.

- **URL**: `GET /api/teacher/events/{id}/zoom-start-link`
- **Auth Required**: Yes
- **Body**: none
- **Success Response** `200`:
  ```json
  {
    "message": "Start link retrieved successfully",
    "start_link": "https://us05web.zoom.us/s/12345678?zak=...",
    "meeting_link": "https://us05web.zoom.us/j/12345678?pwd=..."
  }
  ```
- **Error Responses**:
  - `403` — teacher is not a manager of this event
  - `404` — event not found
  - `422` — this event has no Zoom meeting to start
  - `502` — Zoom could not be reached (error message included)

---

## Event Type Reference

| Value | Meaning |
|-------|---------|
| `class` | A teaching class or lesson |
| `meeting` | A parent-teacher or staff meeting |
| `other` | Any other type of event |
