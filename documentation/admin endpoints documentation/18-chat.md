# Chat (Admin)

Admins can open conversations with any student, reply to student messages, view message history, track unread counts, and archive closed threads.

The same underlying models (`Chat`, `Message`) are shared with the student-side. Messages are broadcast in real-time via Laravel Echo / Pusher.

---

## Flow

```
POST /api/admin/chats/student          → open (or resume) a chat with a student
GET  /api/admin/chats                  → list all admin's chats
GET  /api/admin/chats/{id}             → read full message history (marks messages read)
POST /api/admin/chats/{id}/messages    → send a reply
GET  /api/admin/chats/unread/count     → total unread messages
PUT  /api/admin/chats/{id}/archive     → archive a closed thread
```

---

## Open a Chat with a Student

`POST /api/admin/chats/student`

Creates a new chat thread between the admin and the specified student. If a chat already exists it is returned (and re-activated if previously archived).

```json
{ "student_id": 12 }
```

| Field | Type | Required |
|-------|------|----------|
| `student_id` | integer | Yes — must be a valid student user ID |

**Response** `201` (new) or `200` (existing):
```json
{
  "message": "Chat created successfully",
  "chat": {
    "id": 1,
    "admin_id": 1,
    "student_id": 12,
    "teacher_id": null,
    "last_message_at": "2026-03-07T15:00:00.000000Z",
    "is_active": true,
    "admin": { ... },
    "student": { "id": 12, "username": "john_doe" }
  }
}
```

**Errors**: `403` if the authenticated user is not an admin, `422` if `student_id` is invalid.

---

## List All Chats

`GET /api/admin/chats`

Returns all active chats belonging to this admin, ordered by most recent message. Includes the last message for preview.

**Response** `200`:
```json
{
  "message": "Chats retrieved successfully",
  "chats": [
    {
      "id": 1,
      "student_id": 12,
      "admin_id": 1,
      "last_message_at": "2026-03-07T15:30:00.000000Z",
      "is_active": true,
      "student": { "id": 12, "username": "john_doe" },
      "messages": [
        { "id": 5, "content": "Hello, I have a question.", "sender_id": 12, "read_at": "2026-03-07T15:31:00.000000Z" }
      ]
    }
  ]
}
```

---

## Get Chat with Full Message History

`GET /api/admin/chats/{id}`

Returns the full message thread. All unread messages from the student are automatically marked as read.

Each message includes a `sender_role` field (`"admin"`, `"student"`, or `"teacher"`) and the full `sender` object so the client always knows who sent it.

**Response** `200`:
```json
{
  "message": "Chat retrieved successfully",
  "chat": {
    "id": 1,
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

**Errors**: `403` if the chat does not belong to this admin, `404` if not found.

---

## Send a Message

`POST /api/admin/chats/{id}/messages`

```json
{ "content": "Of course! Your score was 87/100." }
```

The admin must own the chat (`admin_id` matches). The message is broadcast in real-time to both the student and the admin via the private Pusher channel `chat.{id}`.

**Response** `200`:
```json
{
  "message": "Message sent successfully",
  "chat_message": {
    "id": 6,
    "chat_id": 1,
    "content": "Of course! Your score was 87/100.",
    "sender_id": 1,
    "sender_type": "App\\Models\\Admin",
    "read_at": null,
    "created_at": "2026-03-07T15:35:00.000000Z",
    "sender_role": "admin",
    "sender": { "id": 1, "username": "admin_user" }
  }
}
```

**Errors**: `403` if the admin does not own the chat.

---

## Unread Message Count

`GET /api/admin/chats/unread/count`

Returns the total number of unread messages sent by students across all the admin's chats.

**Response** `200`:
```json
{
  "message": "Unread count retrieved successfully",
  "unread_count": 4
}
```

---

## Archive a Chat

`PUT /api/admin/chats/{id}/archive`

Marks the chat as inactive (`is_active: false`). It will no longer appear in the default list. The admin must own the chat.

**Response** `200`:
```json
{
  "message": "Chat archived successfully",
  "chat": { "id": 1, "is_active": false }
}
```
