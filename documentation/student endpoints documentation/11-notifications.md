# Notifications

Students receive notifications created by the admin or system (e.g. for exams, homework, payments). These endpoints allow students to view and manage their own notifications.

## List Notifications

`GET /api/student/notifications`

Optional query parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `is_seen` | boolean | Filter by seen status (`true` / `false`) |
| `notification_type` | string | One of `Exam`, `Schedule`, `Homework`, `Message`, `Payment` |
| `notification_source` | string | One of `Admin`, `Teacher`, `Student` |
| `notification_time` | date | Filter by exact date (`YYYY-MM-DD`) |

**Response** `200`:
```json
{
  "message": "Notifications retrieved successfully",
  "notifications": [
    {
      "id": 1,
      "notification_owner": 12,
      "notification_message": "Your exam result is available",
      "notification_time": "2026-03-08T10:00:00.000000Z",
      "is_seen": false,
      "notification_source": "Admin",
      "notification_type": "Exam",
      "created_at": "2026-03-08T10:00:00.000000Z",
      "updated_at": "2026-03-08T10:00:00.000000Z"
    }
  ]
}
```

---

## Mark All as Seen

`PUT /api/student/notifications/seen-all`

Marks all unseen notifications for the authenticated student as read.

**Response** `200`:
```json
{ "message": "All notifications marked as seen" }
```

---

## Mark One as Seen

`PUT /api/student/notifications/{id}/seen`

**Response** `200`:
```json
{
  "message": "Notification marked as seen",
  "notification": {
    "id": 1,
    "is_seen": true,
    ...
  }
}
```

**Errors**: `404` if notification not found or not owned by the student.

---

## Delete Notification

`DELETE /api/student/notifications/{id}`

**Response** `200`:
```json
{ "message": "Notification deleted successfully" }
```

**Errors**: `404` if notification not found or not owned by the student.
