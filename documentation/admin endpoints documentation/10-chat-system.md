# Chat System

This section covers the shared chat endpoints used by both students and admins.

> **How chats work**
> - A chat is always between one **student** and one **admin**.
> - A **student** creates the chat via `POST /api/chats` (no body required).
> - An **admin** creates the chat via `POST /api/admin-chats` (requires `student_id`).
> - Both parties send messages through `POST /api/admin-chats/{id}/messages`.
> - Real-time delivery is handled via Pusher on the private channel `chat.{chatId}`.

---

## Chat System Setup

### Prerequisites

1. **Pusher Account**
   - Sign up at [https://pusher.com/](https://pusher.com/) and create a Channels app
   - Add credentials to `.env`:
     ```
     BROADCAST_DRIVER=pusher
     PUSHER_APP_ID=your_app_id
     PUSHER_APP_KEY=your_app_key
     PUSHER_APP_SECRET=your_app_secret
     PUSHER_APP_CLUSTER=your_app_cluster
     ```

2. **Required package**
   ```bash
   composer require pusher/pusher-php-server
   ```

### Frontend Integration

```javascript
import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Listen for new messages in a chat
Echo.private(`chat.${chatId}`)
    .listen('.message.sent', (e) => {
        console.log(e.message);
    });
```

---

## Endpoints

### Student: Create a Chat

Students use this endpoint to open a conversation with an admin. The admin is
selected automatically from `DEFAULT_ADMIN_ID` in `.env`, or the first admin in
the database if that variable is not set. If a chat with that admin already
exists it is returned instead of creating a duplicate.

- **URL**: `/api/chats`
- **Method**: `POST`
- **Auth Required**: Yes — **student token only**
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**: none
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
  { "message": "Only students can use this endpoint" }
  ```
  HTTP Status: `403`
  ```json
  { "message": "No admin available for chat" }
  ```
  HTTP Status: `422`

---

### Get All Chats

Returns all active chats for the authenticated user, ordered by most recent
message. Includes a preview of the latest message per chat.

- **URL**: `/api/chats`
- **Method**: `GET`
- **Auth Required**: Yes — student or admin token
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Chats retrieved successfully",
    "chats": [
      {
        "id": 1,
        "student_id": 5,
        "admin_id": 1,
        "teacher_id": null,
        "last_message_at": "2026-02-19T14:30:00.000000Z",
        "is_active": true,
        "created_at": "2026-02-15T10:00:00.000000Z",
        "updated_at": "2026-02-19T14:30:00.000000Z",
        "student": { "id": 5, "username": "student1", "role": "student" },
        "admin":   { "id": 1, "username": "admin1",   "role": "admin" },
        "messages": [
          {
            "id": 10,
            "chat_id": 1,
            "content": "When is the next assignment due?",
            "sender_id": 5,
            "sender_type": "App\\Models\\User",
            "read_at": null,
            "created_at": "2026-02-19T14:30:00.000000Z",
            "updated_at": "2026-02-19T14:30:00.000000Z"
          }
        ]
      }
    ]
  }
  ```

---

### Get Chat with Messages

Returns a specific chat with its full message history. Automatically marks all
incoming messages (sent by the other party) as read.

- **URL**: `/api/chats/{id}`
- **Method**: `GET`
- **Auth Required**: Yes — the student or admin who belongs to this chat
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Chat retrieved successfully",
    "chat": {
      "id": 1,
      "student_id": 5,
      "admin_id": 1,
      "teacher_id": null,
      "last_message_at": "2026-02-19T14:30:00.000000Z",
      "is_active": true,
      "created_at": "2026-02-15T10:00:00.000000Z",
      "updated_at": "2026-02-19T14:30:00.000000Z",
      "student": { "id": 5, "username": "student1", "role": "student" },
      "admin":   { "id": 1, "username": "admin1",   "role": "admin" },
      "messages": [
        {
          "id": 10,
          "chat_id": 1,
          "content": "When is the next assignment due?",
          "sender_id": 5,
          "sender_type": "App\\Models\\User",
          "read_at": null,
          "created_at": "2026-02-19T14:30:00.000000Z",
          "updated_at": "2026-02-19T14:30:00.000000Z",
          "sender": { "id": 5, "username": "student1", "role": "student" }
        },
        {
          "id": 9,
          "chat_id": 1,
          "content": "The assignment is due on Friday.",
          "sender_id": 1,
          "sender_type": "App\\Models\\User",
          "read_at": "2026-02-19T14:25:00.000000Z",
          "created_at": "2026-02-19T14:20:00.000000Z",
          "updated_at": "2026-02-19T14:25:00.000000Z",
          "sender": { "id": 1, "username": "admin1", "role": "admin" }
        }
      ]
    }
  }
  ```
- **Note**: Messages are automatically marked as read upon opening. There is no
  separate "mark as read" endpoint.
- **Error Responses**:
  ```json
  { "message": "Chat not found" }
  ```
  HTTP Status: `404`
  ```json
  { "message": "Unauthorized" }
  ```
  HTTP Status: `403`

---

### Get Unread Message Count

Returns the number of unread messages sent by the other party across all of the
authenticated user's chats.

- **URL**: `/api/chats/unread/count`
- **Method**: `GET`
- **Auth Required**: Yes — student or admin token
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Unread count retrieved successfully",
    "unread_count": 3
  }
  ```

---

### Archive a Chat

Marks a chat as inactive. Archived chats are excluded from `GET /api/chats`.

- **URL**: `/api/chats/{id}/archive`
- **Method**: `PUT`
- **Auth Required**: Yes — **admin token only**
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Chat archived successfully",
    "chat": {
      "id": 1,
      "student_id": 5,
      "admin_id": 1,
      "teacher_id": null,
      "last_message_at": "2026-02-19T14:30:00.000000Z",
      "is_active": false,
      "created_at": "2026-02-15T10:00:00.000000Z",
      "updated_at": "2026-02-20T16:00:00.000000Z"
    }
  }
  ```
- **Error Responses**:
  ```json
  { "message": "Only admins can archive chats" }
  ```
  HTTP Status: `403`

---

## Broadcasting Authentication

Required by the Pusher client to subscribe to private channels.

- **URL**: `/api/broadcasting/auth`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "socket_id": "123.456",
    "channel_name": "private-chat.1"
  }
  ```
- **Success Response**:
  ```json
  {
    "auth": "a1b2c3d4e5f6g7h8i9j0..."
  }
  ```
