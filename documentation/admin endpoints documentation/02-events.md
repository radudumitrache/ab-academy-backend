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
