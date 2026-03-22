# Attendance

Teachers record attendance. Admins can read attendance for any group or event. Students can see their own attendance status on each event they are invited to.

---

## Attendance Status Values

| Value | Meaning |
|-------|---------|
| `present` | Student was present |
| `absent` | Student was absent |
| `motivated_absent` | Student was absent with a valid reason |

---

## View Group Attendance

Returns all attendance records for a group. Only the group's teacher may call this. Optionally filter to a single session date.

> `session_date` and `session_time` are returned in the requesting teacher's timezone. When filtering by `session_date`, provide the date in your own timezone — the API translates it to the appropriate UTC range internally.

- **URL**: `GET /api/teacher/groups/{id}/attendance`
- **Auth Required**: Yes
- **Query Parameters**:

  | Parameter | Type | Required | Notes |
  |-----------|------|----------|-------|
  | `session_date` | string | No | Filter to a single session (`YYYY-MM-DD`) — in your timezone |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance retrieved successfully",
    "group_id": 3,
    "group_name": "English B2 Morning",
    "attendance": [
      { "student_id": 12, "username": "student1", "email": "s1@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "present" },
      { "student_id": 15, "username": "student2", "email": "s2@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "absent" }
    ]
  }
  ```

- **Error Responses**:
  - **404** — group not found
  - **403** — teacher is not the group's teacher

---

## Record Group Attendance

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
  | `session_date` | string | Yes | `YYYY-MM-DD` in your timezone |
  | `session_time` | string | Yes | `HH:MM` in your timezone — should match a slot in `schedule_days` |
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

## View Event Attendance

Returns all guests for an event (direct + from guest groups) with their recorded status. Only the event organizer or a direct guest (teacher) may call this.

- **URL**: `GET /api/teacher/events/{id}/attendance`
- **Auth Required**: Yes

- **Success Response** `200`:
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

  > `status: null` means the guest has not been marked yet.

- **Error Responses**:
  - **404** — event not found
  - **403** — teacher is not the organizer or a guest

---

## Record Event Attendance

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
  | `attendance.*.student_id` | integer | Yes | Must be a valid user ID and appear in the event's `guests` array or be a member of an invited `guest_groups` group |
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
  - **422** — one or more student IDs are not on the guest list (direct or via group):
    ```json
    {
      "message": "Some users are not on the guest list for this event",
      "not_on_guest_list": [99]
    }
    ```

---

## Admin: View Event Attendance

Returns all guests for an event (direct + from guest groups) with their recorded attendance status. Students not yet marked have `status: null`.

- **URL**: `GET /api/admin/events/{id}/attendance`
- **Auth Required**: Yes (admin)

- **Success Response** `200`:
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

- **Error Responses**:
  - **404** — event not found:
    ```json
    { "message": "Event not found" }
    ```

---

## Admin: View Group Attendance

Returns all attendance records for a group, ordered by session date and time. Can be filtered to a specific session date.

> `session_date` and `session_time` are returned in the requesting admin's timezone. When filtering by `session_date`, provide the date in your own timezone — the API translates it to the appropriate UTC range internally.

- **URL**: `GET /api/admin/groups/{id}/attendance`
- **Auth Required**: Yes (admin)
- **Query Parameters**:

  | Parameter | Type | Required | Notes |
  |-----------|------|----------|-------|
  | `session_date` | string | No | Filter to a single session (`YYYY-MM-DD`) — in your timezone |

- **Success Response** `200`:
  ```json
  {
    "message": "Attendance retrieved successfully",
    "group_id": 3,
    "group_name": "English B2 Morning",
    "attendance": [
      { "student_id": 12, "username": "student1", "email": "s1@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "present" },
      { "student_id": 15, "username": "student2", "email": "s2@example.com", "session_date": "2026-03-20", "session_time": "09:00:00", "status": "absent" }
    ]
  }
  ```

- **Error Responses**:
  - **404** — group not found:
    ```json
    { "message": "Group not found" }
    ```

---

## Student: Attendance Status on Events

Students see their own attendance status directly on each event object returned by the event list and show endpoints. No separate attendance endpoint is needed.

- **Field**: `attendance_status` — present on every event object in `GET /api/student/events` and `GET /api/student/events/{id}`
- **Values**: `"present"`, `"absent"`, `"motivated_absent"`, or `null` (not yet marked)

```json
{
  "id": 5,
  "title": "English Class",
  "event_date": "2026-03-20",
  "attendance_status": "present",
  ...
}
```
