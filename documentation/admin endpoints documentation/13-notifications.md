# Notifications

This section covers the notification system used to alert users about platform activity.

---

## Data Model

| Column | Type | Description |
|--------|------|-------------|
| `id` | integer | Primary key |
| `notification_owner` | integer | FK → `users.id` — the user who receives the notification |
| `notification_message` | text | Human-readable notification text |
| `notification_time` | datetime | When the notification was triggered |
| `is_seen` | boolean | Whether the owner has read the notification (default: `false`) |
| `notification_source` | enum | Who generated the notification: `Admin`, `Student`, `Teacher` |
| `notification_type` | enum | What the notification is about: `Exam`, `Schedule`, `Homework`, `Message`, `Payment` |
| `created_at` | datetime | Record creation timestamp |
| `updated_at` | datetime | Record update timestamp |

### Allowed Values

| Field | Allowed values |
|-------|----------------|
| `notification_source` | `Admin`, `Student`, `Teacher` |
| `notification_type` | `Exam`, `Schedule`, `Homework`, `Message`, `Payment` |

---

## Endpoints

> **Note**: All endpoints below require a valid Bearer token in the `Authorization` header.

---

### Get Notifications

Returns all notifications for the authenticated user, ordered by most recent.

- **URL**: `/api/admin/notifications`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters** (all optional):

  | Parameter | Type | Description |
  |-----------|------|-------------|
  | `is_seen` | boolean | Filter by read status (`true` / `false`) |
  | `notification_type` | string | Filter by type (`Exam`, `Schedule`, `Homework`, `Message`, `Payment`) |
  | `notification_source` | string | Filter by source (`Admin`, `Student`, `Teacher`) |
  | `notification_time` | date | Filter by date (`YYYY-MM-DD`) |

- **Success Response** `200`:
  ```json
  {
    "message": "Notifications retrieved successfully",
    "notifications": [
      {
        "id": 1,
        "notification_owner": 3,
        "notification_message": "Your exam result has been posted.",
        "notification_time": "2026-02-24T10:00:00.000000Z",
        "is_seen": false,
        "notification_source": "Admin",
        "notification_type": "Exam",
        "created_at": "2026-02-24T10:00:00.000000Z",
        "updated_at": "2026-02-24T10:00:00.000000Z"
      }
    ]
  }
  ```

---

### Create a Notification

Creates a new notification for a user.

- **URL**: `/api/admin/notifications`
- **Method**: `POST`
- **Auth Required**: Yes — admin token
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "notification_owner": 3,
    "notification_message": "Your homework has been graded.",
    "notification_time": "2026-02-24T10:00:00.000000Z",
    "notification_source": "Teacher",
    "notification_type": "Homework"
  }
  ```
- **Field Notes**:
  - `notification_owner` (required): ID of the user to notify
  - `notification_message` (required): Text content of the notification
  - `notification_time` (optional): Defaults to `now()` if omitted
  - `notification_source` (optional): One of `Admin`, `Student`, `Teacher`
  - `notification_type` (optional): One of `Exam`, `Schedule`, `Homework`, `Message`, `Payment`
- **Success Response** `201`:
  ```json
  {
    "message": "Notification created successfully",
    "notification": {
      "id": 2,
      "notification_owner": 3,
      "notification_message": "Your homework has been graded.",
      "notification_time": "2026-02-24T10:00:00.000000Z",
      "is_seen": false,
      "notification_source": "Teacher",
      "notification_type": "Homework",
      "created_at": "2026-02-24T10:00:00.000000Z",
      "updated_at": "2026-02-24T10:00:00.000000Z"
    }
  }
  ```
- **Error Response** `422`:
  ```json
  {
    "message": "Validation failed",
    "errors": {
      "notification_owner": ["The notification owner field is required."],
      "notification_source": ["The selected notification source is invalid."],
      "notification_type": ["The selected notification type is invalid."]
    }
  }
  ```

---

### Mark Notification as Seen

Marks a single notification as read.

- **URL**: `/api/admin/notifications/{id}/seen`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  {
    "message": "Notification marked as seen",
    "notification": {
      "id": 1,
      "is_seen": true,
      "...": "..."
    }
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Notification not found" }
  ```
  HTTP Status: `404`

---

### Mark All Notifications as Seen

Marks all unseen notifications for the authenticated user as read.

- **URL**: `/api/admin/notifications/seen-all`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  {
    "message": "All notifications marked as seen"
  }
  ```

---

### Delete a Notification

Deletes a single notification.

- **URL**: `/api/admin/notifications/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes — admin token
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** `200`:
  ```json
  {
    "message": "Notification deleted successfully"
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Notification not found" }
  ```
  HTTP Status: `404`

---

## Notification Types Reference

| Type | When to use |
|------|-------------|
| `Exam` | Exam scheduled, result posted, status changed |
| `Schedule` | Group schedule created or updated |
| `Homework` | Homework assigned or graded |
| `Message` | New chat message received |
| `Payment` | Invoice issued or payment status changed |

## Notification Sources Reference

| Source | Who triggers it |
|--------|-----------------|
| `Admin` | An admin action (e.g. invoice created, student enrolled) |
| `Teacher` | A teacher action (e.g. homework assigned, exam graded) |
| `Student` | A student action (e.g. message sent) |
