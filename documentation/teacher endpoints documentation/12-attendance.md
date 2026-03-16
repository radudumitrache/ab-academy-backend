# Attendance

Teachers can record attendance for both group sessions and events.

---

## Attendance Status Values

| Value | Meaning |
|-------|---------|
| `present` | Student was present |
| `absent` | Student was absent |
| `motivated_absent` | Student was absent with a valid reason |

---

## Group Attendance

Records attendance for a specific group session. Only the group's teacher may call this.
Uses `updateOrCreate` — calling it again for the same `(group_id, student_id, session_date, session_time)` overwrites the previous status.

- **URL**: `POST /api/teacher/groups/{id}/attendance`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "session_date": "2026-03-20",
    "session_time": "09:00",
    "attendance": [
      { "student_id": 12, "status": "present" },
      { "student_id": 15, "status": "absent" },
      { "student_id": 19, "status": "motivated_absent" }
    ]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `session_date` | string | Yes | `YYYY-MM-DD` format |
  | `session_time` | string | Yes | `HH:MM` format |
  | `attendance` | array | Yes | At least one entry required |
  | `attendance.*.student_id` | integer | Yes | Must be a valid user ID and a member of the group |
  | `attendance.*.status` | string | Yes | `present`, `absent`, or `motivated_absent` |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance recorded successfully",
    "session_date": "2026-03-20",
    "session_time": "09:00",
    "attendance": [
      { "student_id": 12, "status": "present" },
      { "student_id": 15, "status": "absent" },
      { "student_id": 19, "status": "motivated_absent" }
    ]
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```
  - **403** — teacher is not the group's teacher:
    ```json
    { "message": "Unauthorized" }
    ```
  - **422** — validation failed or student not a group member:
    ```json
    { "message": "Student 99 is not a member of this group" }
    ```

---

## Event Attendance

Records which invited guests attended an event. Only the event organizer may call this.
Uses `updateOrCreate` keyed on `(event_id, student_id)` — re-submitting overwrites the previous status.
All provided `student_id` values must already be on the event's guest list.

- **URL**: `PUT /api/teacher/events/{id}/attendance`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "attendance": [
      { "student_id": 12, "status": "present" },
      { "student_id": 19, "status": "absent" }
    ]
  }
  ```
- **Field Notes**:

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `attendance` | array | Yes | One entry per guest to record |
  | `attendance.*.student_id` | integer | Yes | Must be a valid user ID and appear in the event's `guests` array |
  | `attendance.*.status` | string | Yes | `present`, `absent`, or `motivated_absent` |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance recorded successfully",
    "attendance": [
      { "student_id": 12, "username": "student1", "status": "present" },
      { "student_id": 19, "username": "teacher2", "status": "absent" }
    ]
  }
  ```

  > The response returns **all** attendance records for the event (not just the ones submitted in this call).

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```
  - **403** — teacher is not the event organizer:
    ```json
    { "message": "Unauthorized — only the event organizer can mark attendance" }
    ```
  - **422** — one or more student IDs are not on the guest list:
    ```json
    {
      "message": "Some users are not on the guest list for this event",
      "not_on_guest_list": [99]
    }
    ```
