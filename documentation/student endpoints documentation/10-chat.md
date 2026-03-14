# Chat (Student)

Students can open a conversation with an admin, send messages, view message history, and track unread counts.

The same underlying models (`Chat`, `Message`) are shared with the admin-side. Messages are broadcast in real-time via Laravel Echo / Pusher.

---

## Flow

```
POST /api/student/chats/admin          → open (or resume) a chat with an admin
GET  /api/student/chats                → list all student's chats
GET  /api/student/chats/unread/count   → total unread messages
GET  /api/student/chats/{id}           → read full message history (marks messages read)
POST /api/student/chats/{id}/messages  → send a reply
```

---

## Open a Chat with an Admin

`POST /api/student/chats/admin`

Creates a new chat thread between the student and an admin. If a chat already exists it is returned (and re-activated if previously archived).

No request body is required. To send an opening message, use the returned chat `id` with the send-message endpoint.

**Response** `201` (new) or `200` (existing):
```json
{
  "message": "Chat created successfully",
  "chat": {
    "id": 2,
    "student_id": 12,
    "admin_id": 1,
    "teacher_id": null,
    "last_message_at": null,
    "is_active": true,
    "admin": { "id": 1, "username": "admin_user" },
    "student": { "id": 12, "username": "john_doe" }
  }
}
```

---

## List All Chats

`GET /api/student/chats`

Returns all active chats belonging to this student, ordered by most recent message. Includes the last message for preview.

**Response** `200`:
```json
{
  "message": "Chats retrieved successfully",
  "chats": [
    {
      "id": 2,
      "student_id": 12,
      "admin_id": 1,
      "teacher_id": null,
      "last_message_at": "2026-03-07T15:30:00.000000Z",
      "is_active": true,
      "admin": { "id": 1, "username": "admin_user" },
      "messages": [
        { "id": 5, "content": "Hello, I have a question.", "sender_id": 12, "read_at": null }
      ]
    }
  ]
}
```

---

## Unread Message Count

`GET /api/student/chats/unread/count`

Returns the total number of unread messages sent by admins across all the student's chats.

**Response** `200`:
```json
{
  "message": "Unread count retrieved successfully",
  "unread_count": 2
}
```

---

## Get Chat with Full Message History

`GET /api/student/chats/{id}`

Returns the full message thread. All unread messages from the admin are automatically marked as read.

Each message includes a `sender_role` field (`"admin"` or `"student"`) and a trimmed `sender` object (`id` and `username` only) so the client always knows who sent it.

**Response** `200`:
```json
{
  "message": "Chat retrieved successfully",
  "chat": {
    "id": 2,
    "student_id": 12,
    "admin_id": 1,
    "messages": [
      {
        "id": 5,
        "content": "Hello, I have a question about my exam result.",
        "sender_id": 12,
        "sender_type": "App\\Models\\Student",
        "read_at": "2026-03-07T15:31:00.000000Z",
        "created_at": "2026-03-07T15:30:00.000000Z",
        "sender_role": "student",
        "sender": { "id": 12, "username": "john_doe" }
      },
      {
        "id": 6,
        "content": "Of course! Your score was 87/100.",
        "sender_id": 1,
        "sender_type": "App\\Models\\Admin",
        "read_at": null,
        "created_at": "2026-03-07T15:35:00.000000Z",
        "sender_role": "admin",
        "sender": { "id": 1, "username": "admin_user" }
      }
    ]
  }
}
```

**Errors**: `403` if the chat does not belong to this student, `404` if not found.

---

## Send a Message

`POST /api/student/chats/{id}/messages`

```json
{ "content": "Thank you, that clears it up!" }
```

The student must be a participant in the chat. The message is broadcast in real-time to the admin.

**Response** `200`:
```json
{
  "message": "Message sent successfully",
  "chat_message": {
    "id": 7,
    "chat_id": 2,
    "content": "Thank you, that clears it up!",
    "sender_id": 12,
    "sender_type": "App\\Models\\Student",
    "read_at": null,
    "created_at": "2026-03-07T15:40:00.000000Z",
    "sender_role": "student",
    "sender": { "id": 12, "username": "john_doe" }
  }
}
```

**Errors**: `403` if the student is not a participant in the chat.
