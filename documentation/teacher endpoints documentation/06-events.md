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
  "present_guests": null,
  "event_meet_link": "https://meet.google.com/abc-defg-hij",
  "event_notes": "Bring attendance records.",
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
| `guests` | Array of user IDs invited to the event |
| `present_guests` | Array of guest IDs who attended (managed separately) |
| `event_meet_link` | Optional meeting URL |
| `event_notes` | Optional free-text notes |
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

Returns a single event. Only accessible if the teacher is the organizer or is in the guest list.

- **URL**: `/api/teacher/events/{id}`
- **Method**: `GET`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Event retrieved successfully",
    "event": { ... }
  }
  ```

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is neither organizer nor guest:
    ```json
    { "message": "Unauthorized" }
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
  | `guests` | array | No | Array of user IDs to invite |
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
    "guests": [12, 15, 20]
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

## Event Type Reference

| Value | Meaning |
|-------|---------|
| `class` | A teaching class or lesson |
| `meeting` | A parent-teacher or staff meeting |
| `other` | Any other type of event |
