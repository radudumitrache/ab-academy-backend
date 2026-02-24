# Admin Chat System

This section covers the endpoints used specifically for admin-initiated chats
and sending messages in admin–student conversations.

> For student-initiated chat creation and shared endpoints (list, show, unread
> count, archive), see [10-chat-system.md](10-chat-system.md).

---

## Endpoints

### Admin: Create a Chat

Opens a new conversation between the authenticated admin and a specified student.
If a chat between these two users already exists it is returned (and reactivated
if it was archived) instead of creating a duplicate.

- **URL**: `/api/admin-chats`
- **Method**: `POST`
- **Auth Required**: Yes — **admin token only**
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "student_id": 5
  }
  ```
- **Field notes**:
  - `student_id` (required): must be the ID of a user with `role = student`
- **Success Response** `201`:
  ```json
  {
    "message": "Chat created successfully",
    "chat": {
      "id": 2,
      "student_id": 5,
      "admin_id": 1,
      "teacher_id": null,
      "last_message_at": "2026-02-21T15:00:00.000000Z",
      "is_active": true,
      "created_at": "2026-02-21T15:00:00.000000Z",
      "updated_at": "2026-02-21T15:00:00.000000Z",
      "student": { "id": 5, "username": "student1", "role": "student" },
      "admin":   { "id": 1, "username": "admin1",   "role": "admin" }
    }
  }
  ```
- **If chat already exists** `200`:
  ```json
  {
    "message": "Chat already exists",
    "chat": { "...": "same shape, includes messages.sender" }
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Only admins can use this endpoint" }
  ```
  HTTP Status: `403`
  ```json
  {
    "message": "Validation failed",
    "errors": { "student_id": ["The student id field is required."] }
  }
  ```
  HTTP Status: `422`
  ```json
  { "message": "Invalid student ID" }
  ```
  HTTP Status: `422`

---

### Send a Message

Sends a message in an existing admin–student chat. Both the student and the
admin who belong to the chat are authorised to send. The message is broadcast
in real-time on the private Pusher channel `chat.{chatId}`.

- **URL**: `/api/admin-chats/{id}/messages`
- **Method**: `POST`
- **Auth Required**: Yes — student or admin token (must be a participant in the chat)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "content": "Hello, I have a question about my enrollment."
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Message sent successfully",
    "chat_message": {
      "id": 11,
      "chat_id": 2,
      "content": "Hello, I have a question about my enrollment.",
      "sender_id": 5,
      "sender_type": "App\\Models\\User",
      "read_at": null,
      "created_at": "2026-02-21T15:30:00.000000Z",
      "updated_at": "2026-02-21T15:30:00.000000Z",
      "sender": { "id": 5, "username": "student1", "role": "student" }
    }
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Chat not found" }
  ```
  HTTP Status: `404`
  ```json
  { "message": "Unauthorized to send message in this chat" }
  ```
  HTTP Status: `403`
  ```json
  {
    "message": "Validation failed",
    "errors": { "content": ["The content field is required."] }
  }
  ```
  HTTP Status: `422`

---

## Configuration

The default admin used for student-initiated chats is controlled by:

```
DEFAULT_ADMIN_ID=1
```

in your `.env` file. If not set, the system falls back to the first admin found
in the database.

```php
// config/chat.php
return [
    'default_admin_id' => env('DEFAULT_ADMIN_ID', null),
];
```

---

## Quick Reference

| Who | Endpoint | Purpose |
|-----|----------|---------|
| Student | `POST /api/chats` | Open a chat with admin (no body) |
| Admin | `POST /api/admin-chats` | Open a chat with a student (`student_id` required) |
| Student or Admin | `POST /api/admin-chats/{id}/messages` | Send a message |
| Student or Admin | `GET /api/chats` | List my chats |
| Student or Admin | `GET /api/chats/{id}` | Open a chat + mark messages as read |
| Student or Admin | `GET /api/chats/unread/count` | Get unread message count |
| Admin only | `PUT /api/chats/{id}/archive` | Archive a chat |
