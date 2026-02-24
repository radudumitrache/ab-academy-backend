# Notifications

This section covers the notification system used to alert users about platform activity.

Notifications are created **automatically** by model observers whenever key platform events occur.
They can also be created manually via the API.

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

## Automatic Notifications

Notifications are dispatched automatically via Eloquent observers registered in
`AppServiceProvider`. No manual API call is needed for these events.

### Exam events (`notification_type: Exam`, `notification_source: Admin`)

Triggered on **create**, **update**, and **delete** of an `Exam`.

**Recipients**: all students enrolled in the exam + teachers of any group that contains at least one of those students.

| Event | Message template |
|-------|-----------------|
| Created | `"A new exam '{name}' has been scheduled for {date}."` |
| Updated | `"The exam '{name}' has been updated. Date: {date}, Status: {status}."` |
| Deleted | `"The exam '{name}' has been cancelled."` |

> If no students are enrolled at the time of the event, no notifications are sent.

---

### Homework events (`notification_type: Homework`, `notification_source: Admin`)

Triggered on **create**, **update**, and **delete** of a `Homework`.

**Recipients**: all user IDs in `people_assigned` + all students who belong to any group in `groups_assigned`.

| Event | Message template |
|-------|-----------------|
| Created | `"New homework '{title}' has been assigned, due on {due_date}."` |
| Updated | `"Homework '{title}' has been updated."` |
| Deleted | `"Homework '{title}' has been removed."` |

---

### Event (Schedule) events (`notification_type: Schedule`, `notification_source: Admin`)

Triggered on **create**, **update**, and **delete** of an `Event`.

**Recipients**: all user IDs in the `guests` array + the `event_organizer`.

| Event | Message template |
|-------|-----------------|
| Created | `"A new event '{title}' has been scheduled for {date}."` |
| Updated | `"The event '{title}' has been updated. Date: {date}."` |
| Deleted | `"The event '{title}' has been cancelled."` |

---

### Message events (`notification_type: Message`, `notification_source: Admin`)

Triggered on **create** of a `Message` **only when the sender is an admin**.

**Recipient**: the student associated with the chat.

| Event | Message template |
|-------|-----------------|
| Created (admin sender) | `"You have a new message from {sender_username}."` |

> No notification is created when a student sends a message.

---

### Invoice events (`notification_type: Payment`, `notification_source: Admin`)

Triggered on **create** of an `Invoice`.

**Recipient**: the student the invoice is assigned to (`student_id`).

| Event | Message template |
|-------|-----------------|
| Created | `"A new invoice '{title}' for {value} {currency} has been issued, due on {due_date}."` |

---

## Endpoints

> All endpoints require a valid Bearer token in the `Authorization` header.

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
        "notification_message": "A new exam 'Math Final' has been scheduled for 2026-03-01.",
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

### Create a Notification (manual)

Creates a notification for a user manually.

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

## Reference Tables

### Notification Types

| Type | Triggered by | Observer |
|------|-------------|----------|
| `Exam` | Exam created, updated, or deleted | `ExamObserver` |
| `Schedule` | Event created, updated, or deleted | `EventObserver` |
| `Homework` | Homework created, updated, or deleted | `HomeworkObserver` |
| `Message` | Admin sends a chat message | `MessageObserver` |
| `Payment` | Invoice created | `InvoiceObserver` |

### Notification Sources

| Source | Who triggers it |
|--------|-----------------|
| `Admin` | All automatic observers + manual admin actions |
| `Teacher` | Manual notifications created by a teacher action |
| `Student` | Manual notifications created by a student action |
