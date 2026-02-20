# Chat System

This section covers the API endpoints for the chat system in the AB Academy platform and provides instructions for setting up the chat system.

## Chat System Setup Instructions

### Prerequisites

1. **Pusher Account**
   - Sign up for a free account at [https://pusher.com/](https://pusher.com/)
   - Create a new Channels app in your Pusher dashboard
   - Note your app credentials (app_id, key, secret, cluster)

2. **Environment Configuration**
   - Add the following variables to your `.env` file:
     ```
     BROADCAST_DRIVER=pusher
     PUSHER_APP_ID=your_app_id
     PUSHER_APP_KEY=your_app_key
     PUSHER_APP_SECRET=your_app_secret
     PUSHER_APP_CLUSTER=your_app_cluster
     ```

3. **Required Packages**
   - Make sure you have the following packages installed:
     ```bash
     composer require pusher/pusher-php-server
     ```

### Frontend Integration

1. **Install Pusher JS Client**
   ```bash
   npm install pusher-js laravel-echo
   ```

2. **Configure Laravel Echo**
   ```javascript
   // resources/js/bootstrap.js
   import Echo from 'laravel-echo';
   
   window.Pusher = require('pusher-js');
   
   window.Echo = new Echo({
       broadcaster: 'pusher',
       key: process.env.MIX_PUSHER_APP_KEY,
       cluster: process.env.MIX_PUSHER_APP_CLUSTER,
       forceTLS: true
   });
   ```

3. **Listen for Events**
   ```javascript
   // Example: Listen for new messages in a chat
   Echo.private(`chat.${chatId}`)
       .listen('MessageSent', (e) => {
           console.log(e.message);
           // Update your UI with the new message
       });
   ```

## Chat API Endpoints

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
        "student_id": 5,
        "teacher_id": 2,
        "last_message_at": "2026-02-19T14:30:00.000000Z",
        "is_active": true,
        "created_at": "2026-02-15T10:00:00.000000Z",
        "updated_at": "2026-02-19T14:30:00.000000Z",
        "student": {
          "id": 5,
          "username": "student1",
          "role": "student"
        },
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "last_message": {
          "id": 10,
          "chat_id": 1,
          "content": "When is the next assignment due?",
          "sender_id": 5,
          "sender_type": "App\\Models\\User",
          "read_at": null,
          "created_at": "2026-02-19T14:30:00.000000Z",
          "updated_at": "2026-02-19T14:30:00.000000Z"
        }
      }
    ]
  }
  ```

### Create a New Chat

- **URL**: `/api/chats`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "teacher_id": 2,
    "student_id": 5
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Chat created successfully",
    "chat": {
      "id": 2,
      "student_id": 5,
      "teacher_id": 2,
      "last_message_at": null,
      "is_active": true,
      "created_at": "2026-02-20T15:00:00.000000Z",
      "updated_at": "2026-02-20T15:00:00.000000Z",
      "student": {
        "id": 5,
        "username": "student1",
        "role": "student"
      },
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

### Get Chat with Messages

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
      "student_id": 5,
      "teacher_id": 2,
      "last_message_at": "2026-02-19T14:30:00.000000Z",
      "is_active": true,
      "created_at": "2026-02-15T10:00:00.000000Z",
      "updated_at": "2026-02-19T14:30:00.000000Z",
      "student": {
        "id": 5,
        "username": "student1",
        "role": "student"
      },
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    },
    "messages": {
      "current_page": 1,
      "data": [
        {
          "id": 10,
          "chat_id": 1,
          "content": "When is the next assignment due?",
          "sender_id": 5,
          "sender_type": "App\\Models\\User",
          "read_at": null,
          "created_at": "2026-02-19T14:30:00.000000Z",
          "updated_at": "2026-02-19T14:30:00.000000Z",
          "sender": {
            "id": 5,
            "username": "student1",
            "role": "student"
          }
        },
        {
          "id": 9,
          "chat_id": 1,
          "content": "Let me know if you have any questions.",
          "sender_id": 2,
          "sender_type": "App\\Models\\User",
          "read_at": "2026-02-19T14:25:00.000000Z",
          "created_at": "2026-02-19T14:20:00.000000Z",
          "updated_at": "2026-02-19T14:25:00.000000Z",
          "sender": {
            "id": 2,
            "username": "teacher1",
            "role": "teacher"
          }
        }
      ],
      "per_page": 15,
      "total": 2
    }
  }
  ```

### Send a Message

- **URL**: `/api/chats/{id}/messages`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "content": "This is a test message"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Message sent successfully",
    "chat_message": {
      "id": 11,
      "chat_id": 1,
      "content": "This is a test message",
      "sender_id": 2,
      "sender_type": "App\\Models\\User",
      "read_at": null,
      "created_at": "2026-02-20T15:30:00.000000Z",
      "updated_at": "2026-02-20T15:30:00.000000Z",
      "sender": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

### Get Unread Message Count

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
    "message": "Unread message count retrieved successfully",
    "unread_count": 3
  }
  ```

### Mark Messages as Read

- **URL**: `/api/chats/{id}/read`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Messages marked as read successfully"
  }
  ```

### Archive a Chat

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
    "message": "Chat archived successfully",
    "chat": {
      "id": 1,
      "student_id": 5,
      "teacher_id": 2,
      "last_message_at": "2026-02-19T14:30:00.000000Z",
      "is_active": false,
      "created_at": "2026-02-15T10:00:00.000000Z",
      "updated_at": "2026-02-20T16:00:00.000000Z"
    }
  }
  ```

## Broadcasting Authentication

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
