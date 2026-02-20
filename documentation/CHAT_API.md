# Chat API Documentation

This document outlines the API endpoints for the real-time chat system that connects teachers and students across the AB Academy platform.

## Setup Requirements

1. Pusher account and app credentials
2. Laravel Echo for frontend integration
3. Authentication token for API access

## API Endpoints

### Get All Chats

Retrieves all chats for the authenticated user (teacher or student).

- **URL**: `/api/chats`
- **Method**: `GET`
- **Auth Required**: Yes
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
        "teacher_id": 5,
        "student_id": 10,
        "last_message_at": "2026-02-20T12:30:45.000000Z",
        "is_active": true,
        "created_at": "2026-02-15T10:20:30.000000Z",
        "updated_at": "2026-02-20T12:30:45.000000Z",
        "teacher": {
          "id": 5,
          "username": "teacher_name",
          "email": "teacher@example.com",
          "telephone": "1234567890",
          "role": "teacher"
        },
        "student": {
          "id": 10,
          "username": "student_name",
          "email": "student@example.com",
          "telephone": "0987654321",
          "role": "student"
        },
        "messages": [
          {
            "id": 42,
            "chat_id": 1,
            "content": "Latest message content",
            "sender_id": 5,
            "sender_type": "App\\Models\\Teacher",
            "read_at": null,
            "created_at": "2026-02-20T12:30:45.000000Z",
            "updated_at": "2026-02-20T12:30:45.000000Z"
          }
        ]
      }
    ]
  }
  ```

### Create a New Chat

Creates a new chat between a teacher and a student.

- **URL**: `/api/chats`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "teacher_id": 5,
    "student_id": 10
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Chat created successfully",
    "chat": {
      "id": 1,
      "teacher_id": 5,
      "student_id": 10,
      "last_message_at": "2026-02-20T12:30:45.000000Z",
      "is_active": true,
      "created_at": "2026-02-20T12:30:45.000000Z",
      "updated_at": "2026-02-20T12:30:45.000000Z",
      "teacher": {
        "id": 5,
        "username": "teacher_name",
        "email": "teacher@example.com",
        "telephone": "1234567890",
        "role": "teacher"
      },
      "student": {
        "id": 10,
        "username": "student_name",
        "email": "student@example.com",
        "telephone": "0987654321",
        "role": "student"
      }
    }
  }
  ```

### Get Chat with Messages

Retrieves a specific chat with its messages.

- **URL**: `/api/chats/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
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
      "teacher_id": 5,
      "student_id": 10,
      "last_message_at": "2026-02-20T12:30:45.000000Z",
      "is_active": true,
      "created_at": "2026-02-15T10:20:30.000000Z",
      "updated_at": "2026-02-20T12:30:45.000000Z",
      "teacher": {
        "id": 5,
        "username": "teacher_name",
        "email": "teacher@example.com",
        "role": "teacher"
      },
      "student": {
        "id": 10,
        "username": "student_name",
        "email": "student@example.com",
        "role": "student"
      }
    },
    "messages": {
      "current_page": 1,
      "data": [
        {
          "id": 42,
          "chat_id": 1,
          "content": "Hello, how can I help you?",
          "sender_id": 5,
          "sender_type": "App\\Models\\Teacher",
          "read_at": "2026-02-20T12:35:00.000000Z",
          "created_at": "2026-02-20T12:30:45.000000Z",
          "updated_at": "2026-02-20T12:35:00.000000Z",
          "sender": {
            "id": 5,
            "username": "teacher_name",
            "email": "teacher@example.com",
            "role": "teacher"
          }
        },
        {
          "id": 41,
          "chat_id": 1,
          "content": "I have a question about the homework",
          "sender_id": 10,
          "sender_type": "App\\Models\\Student",
          "read_at": "2026-02-20T12:31:00.000000Z",
          "created_at": "2026-02-20T12:29:45.000000Z",
          "updated_at": "2026-02-20T12:31:00.000000Z",
          "sender": {
            "id": 10,
            "username": "student_name",
            "email": "student@example.com",
            "role": "student"
          }
        }
      ],
      "per_page": 50,
      "total": 2
    }
  }
  ```

### Send a Message

Sends a new message in a chat.

- **URL**: `/api/chats/{id}/messages`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "content": "This is a new message"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Message sent successfully",
    "chat_message": {
      "id": 43,
      "chat_id": 1,
      "content": "This is a new message",
      "sender_id": 5,
      "sender_type": "App\\Models\\Teacher",
      "read_at": null,
      "created_at": "2026-02-20T12:40:45.000000Z",
      "updated_at": "2026-02-20T12:40:45.000000Z",
      "sender": {
        "id": 5,
        "username": "teacher_name",
        "email": "teacher@example.com",
        "role": "teacher"
      }
    }
  }
  ```

### Get Unread Message Count

Retrieves the count of unread messages for the authenticated user.

- **URL**: `/api/chats/unread/count`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "unread_count": 5
  }
  ```

### Archive a Chat

Archives (deactivates) a chat.

- **URL**: `/api/chats/{id}/archive`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Chat archived successfully"
  }
  ```

## Real-time Integration with Pusher

### Frontend Setup

1. Install required packages:
```bash
npm install --save laravel-echo pusher-js
```

2. Initialize Laravel Echo in your frontend application:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    cluster: 'your-pusher-cluster',
    forceTLS: true,
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/api/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    headers: {
                        Authorization: `Bearer ${yourAuthToken}`
                    }
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            }
        };
    }
});
```

### Listening for New Messages

```javascript
// Listen for new messages in a specific chat
Echo.private(`chat.${chatId}`)
    .listen('.message.sent', (data) => {
        // Handle the new message
        console.log('New message received:', data.message);
        
        // Update your UI with the new message
        // For example, add it to your messages array
        this.messages.push(data.message);
    });
```

## Implementation Notes

1. The chat system uses polymorphic relationships for message senders, allowing both teachers and students to send messages.
2. Messages are marked as read automatically when a user views a chat.
3. The system supports pagination for message history.
4. Real-time updates are delivered through Pusher private channels.
5. Each chat has a unique channel named `chat.{id}` for broadcasting messages.

## Security Considerations

1. All API endpoints are protected with authentication.
2. Users can only access chats they are part of (as either teacher or student).
3. Broadcasting channels are private, requiring authentication.
4. Message history is paginated to prevent excessive data transfer.
