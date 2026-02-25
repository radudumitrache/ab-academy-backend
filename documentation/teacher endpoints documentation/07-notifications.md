# Notification Management

Teachers can view, mark as seen, and delete notifications that belong to them.

---

## Notification Object

```json
{
  "id": 3,
  "notification_owner": 4,
  "notification_message": "Your exam has been scheduled.",
  "notification_time": "2026-03-01T09:00:00.000000Z",
  "is_seen": false,
  "notification_source": "Admin",
  "notification_type": "Exam",
  "created_at": "2026-02-28T08:00:00.000000Z",
  "updated_at": "2026-02-28T08:00:00.000000Z"
}
```

| Field | Description |
|-------|-------------|
| `id` | Unique notification identifier |
| `notification_owner` | ID of the user this notification belongs to |
| `notification_message` | The notification text |
| `notification_time` | When the notification was issued |
| `is_seen` | Whether the teacher has seen this notification |
| `notification_source` | Who triggered it: `Admin`, `Student`, or `Teacher` |
| `notification_type` | Category: `Exam`, `Schedule`, `Homework`, `Message`, or `Payment` |

---

## List My Notifications

Returns all notifications owned by the authenticated teacher, ordered by most recent first.
Supports optional query-string filters.

- **URL**: `/api/teacher/notifications`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters** (all optional):

  | Parameter | Type | Description |
  |-----------|------|-------------|
  | `is_seen` | boolean | Filter by seen status (`true` or `false`) |
  | `notification_type` | string | Filter by type (`Exam`, `Schedule`, `Homework`, `Message`, `Payment`) |
  | `notification_source` | string | Filter by source (`Admin`, `Student`, `Teacher`) |
  | `notification_time` | date | Filter by date (`YYYY-MM-DD`) |

- **Success Response** `200`:
  ```json
  {
    "message": "Notifications retrieved successfully",
    "notifications": [
      {
        "id": 3,
        "notification_owner": 4,
        "notification_message": "Your exam has been scheduled.",
        "notification_time": "2026-03-01T09:00:00.000000Z",
        "is_seen": false,
        "notification_source": "Admin",
        "notification_type": "Exam"
      }
    ]
  }
  ```

---

## Mark Single Notification as Seen

Marks one notification as seen. The notification must belong to the authenticated teacher.

- **URL**: `/api/teacher/notifications/{id}/seen`
- **Method**: `PUT`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  {
    "message": "Notification marked as seen",
    "notification": { ... }
  }
  ```

- **Error Responses**:
  - **404** — notification not found (or does not belong to teacher):
    ```json
    { "message": "Notification not found" }
    ```

---

## Mark All Notifications as Seen

Marks every unseen notification for the authenticated teacher as seen in one call.

- **URL**: `/api/teacher/notifications/seen-all`
- **Method**: `PUT`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  { "message": "All notifications marked as seen" }
  ```

---

## Delete a Notification

Permanently deletes a single notification. The notification must belong to the authenticated teacher.

- **URL**: `/api/teacher/notifications/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes

- **Success Response** `200`:
  ```json
  { "message": "Notification deleted successfully" }
  ```

- **Error Responses**:
  - **404** — notification not found (or does not belong to teacher):
    ```json
    { "message": "Notification not found" }
    ```

---

## Notification Type & Source Reference

| `notification_type` | Meaning |
|---------------------|---------|
| `Exam` | Related to an exam |
| `Schedule` | Related to a schedule change |
| `Homework` | Related to homework |
| `Message` | A direct message notification |
| `Payment` | Related to a payment |

| `notification_source` | Meaning |
|-----------------------|---------|
| `Admin` | Sent by an administrator |
| `Student` | Triggered by a student action |
| `Teacher` | Triggered by a teacher action |
