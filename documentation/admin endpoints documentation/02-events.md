# Events

This section covers the API endpoints for managing events in the AB Academy platform.

## List All Events

- **URL**: `/api/admin/events`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Events retrieved successfully",
    "events": [
      {
        "id": 1,
        "title": "Math Class",
        "type": "class",
        "event_date": "2026-03-01",
        "event_time": "14:00",
        "event_duration": 60,
        "event_organizer": 2,
        "guests": [3, 4, 5],
        "event_meet_link": "https://meet.example.com/math-class",
        "event_notes": "Bring your textbooks",
        "organizer": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        }
      }
    ]
  }
  ```

## Create Event

- **URL**: `/api/admin/events`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Math Class",
    "type": "class",
    "event_date": "2026-03-01",
    "event_time": "14:00",
    "event_duration": 60,
    "event_organizer": 2,
    "guests": [3, 4, 5],
    "event_meet_link": "https://meet.example.com/math-class",
    "event_notes": "Bring your textbooks"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event created successfully",
    "event": {
      "id": 1,
      "title": "Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Bring your textbooks",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Get Event Details

- **URL**: `/api/admin/events/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event retrieved successfully",
    "event": {
      "id": 1,
      "title": "Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Bring your textbooks",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Update Event

- **URL**: `/api/admin/events/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Math Class",
    "event_notes": "Updated notes"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event updated successfully",
    "event": {
      "id": 1,
      "title": "Updated Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Updated notes",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## Delete Event

- **URL**: `/api/admin/events/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event deleted successfully"
  }
  ```

---

## Create Zoom Meeting

Automatically selects an available (non-conflicting) meeting account and creates a Zoom meeting for the event. Stores the resulting join URL in `event_meet_link` and associates the chosen account via `meeting_account_id`.

- **URL**: `/api/admin/events/{id}/create-zoom-meeting`
- **Method**: `POST`
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
      "meeting_account_id": 1,
      ...
    },
    "meeting_link": "https://us05web.zoom.us/j/12345678?pwd=..."
  }
  ```
- **Error Responses**:
  - `404` — event not found
  - `422` — no available meeting accounts for this time slot
  - `502` — Zoom API call failed (error message included)

**How account selection works**: All active meeting accounts that are already assigned to another event with an overlapping time window on the same date are excluded. The first remaining active account is used.
