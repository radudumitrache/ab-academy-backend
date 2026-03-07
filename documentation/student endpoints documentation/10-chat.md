# Chat

Students can send messages to teachers and the admin via the shared chat system. Chats are persistent conversation threads.

---

## List Chats

`GET /api/student/chats`

Returns all chats involving the authenticated student.

**Response** `200`:
```json
{
  "chats": [
    {
      "id": 1,
      "student_id": 12,
      "teacher_id": 4,
      "admin_id": null,
      "last_message_at": "2026-03-07T15:30:00.000000Z",
      "is_active": true
    }
  ]
}
```

---

## Get Chat with Messages

`GET /api/student/chats/{id}`

Returns a single chat thread with all messages.

**Response** `200`:
```json
{
  "chat": {
    "id": 1,
    "student_id": 12,
    "teacher_id": 4,
    "messages": [
      {
        "id": 5,
        "chat_id": 1,
        "content": "Hello, I have a question about homework 3.",
        "sender_id": 12,
        "sender_type": "App\\Models\\Student",
        "read_at": null,
        "created_at": "2026-03-07T15:30:00.000000Z"
      }
    ]
  }
}
```

---

## Send a Message

`POST /api/student/chats/{id}/messages`

```json
{ "content": "Hello, I have a question about homework 3." }
```

**Response** `201` with the new message object.

---

## Unread Message Count

`GET /api/student/chats/unread/count`

**Response** `200`:
```json
{ "unread_count": 3 }
```

---

## Start a Chat with the Admin

`POST /api/student/chats/admin`

Creates a new admin chat thread (or returns the existing one).

No request body required for creation. To send an opening message, use the returned chat `id` with the send message endpoint.

**Response** `201`:
```json
{
  "chat": {
    "id": 2,
    "student_id": 12,
    "admin_id": 1,
    "teacher_id": null,
    "is_active": true
  }
}
```
