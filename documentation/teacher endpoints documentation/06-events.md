# Event Management

Teachers can view events they are involved in (as organizer or guest), create new events with themselves as organizer, and edit or delete events they created.

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
| `event_date` | Date of the event (`YYYY-MM-DD`) |
| `event_time` | Start time (`HH:MM:SS`) |
| `event_duration` | Duration in minutes |
| `event_organizer` | User ID of the organizer |
| `guests` | Array of individual user IDs invited directly |
| `guest_groups` | Array of group IDs — all members of these groups can see the event |
| `present_guests` | Array of guest IDs who attended (managed separately) |
| `event_meet_link` | Guest join URL (populated after Zoom meeting creation) |
| `event_start_link` | Host start URL for the organizer — opens meeting as the owner (populated after Zoom meeting creation). Contains an embedded token that expires ~2 hours after creation. |
| `event_notes` | Optional free-text notes |
| `meeting_account_id` | ID of the `MeetingAccount` used to create the Zoom meeting |
| `organizer` | Full organizer object (eager-loaded) |

---

## List My Events

Returns all events where the authenticated teacher is the organizer **or** appears in the guest list.
Results are ordered by `event_date` and `event_time` ascending.

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

Updates an event. Only the teacher who is the organizer of the event may edit it.
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

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not the organizer:
    ```json
    { "message": "Unauthorized — only the event organizer can edit this event" }
    ```
  - **422** — validation failed (same shape as create).

---

## Delete Event

Deletes an event. Only the teacher who is the organizer may delete it.

- **URL**: `/api/teacher/events/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  { "message": "Event deleted successfully" }
  ```

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not the organizer:
    ```json
    { "message": "Unauthorized — only the event organizer can delete this event" }
    ```

---

## Mark Attendance

Records which invited guests actually attended the event. Overwrites any previously saved attendance.
Only the event organizer may call this endpoint. All provided user IDs must already be on the event's guest list.

- **URL**: `/api/teacher/events/{id}/attendance`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  { "present_guest_ids": [12, 19] }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `present_guest_ids` | array | Yes | IDs of guests who were present — must all be in `guests` |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance recorded successfully",
    "present_guests": [12, 19],
    "present_users": [
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
  - **403** — teacher is not the organizer:
    ```json
    { "message": "Unauthorized — only the event organizer can mark attendance" }
    ```
  - **422** — one or more IDs are not on the guest list:
    ```json
    {
      "message": "Some users are not on the guest list for this event",
      "not_on_guest_list": [99]
    }
    ```

---

## Add Guests by Username

Adds one or more users to the event's guest list by their usernames.
Existing guests are preserved — duplicates are silently ignored.
Only the event organizer may call this endpoint.

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
  - **403** — teacher is not the organizer:
    ```json
    { "message": "Unauthorized — only the event organizer can add guests" }
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

Automatically selects an available (non-conflicting) meeting account and creates a Zoom meeting for the event. Only the event organizer may call this. Stores the guest join URL in `event_meet_link`, the host start URL in `event_start_link`, and associates the chosen account via `meeting_account_id`.

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
  - `403` — authenticated teacher is not the event organizer
  - `404` — event not found
  - `422` — no available meeting accounts for this time slot
  - `502` — Zoom API call failed (error message included)

**How account selection works**: All active meeting accounts already assigned to another event with an overlapping time window on the same date are excluded. The first remaining active account is used.

**Host vs guest URLs**:
- `meeting_link` / `event_meet_link` — the guest join URL. Share this with students and other attendees.
- `start_link` / `event_start_link` — the host start URL. Show this **only to the event organizer**. Opening it launches the meeting with the organizer as the Zoom host (owner). Contains an embedded token that expires approximately 2 hours after creation — for events scheduled further in advance, re-fetch via the Zoom API if needed.

---

## Event Type Reference

| Value | Meaning |
|-------|---------|
| `class` | A teaching class or lesson |
| `meeting` | A parent-teacher or staff meeting |
| `other` | Any other type of event |
