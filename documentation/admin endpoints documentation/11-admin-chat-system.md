# Admin Chat System

This section covers the API endpoints for the student-admin chat system in the AB Academy platform.

## Student-Admin Chat API Endpoints

### Create a New Admin Chat

This endpoint allows a student to create a new chat with an admin. The system will automatically select the admin based on the configuration.

- **URL**: `/api/admin-chats`
- **Method**: `POST`
- **Auth Required**: Yes (Student)
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
- **Success Response**:
  ```json
  {
    "message": "Chat created successfully",
    "chat": {
      "id": 2,
      "student_id": 5,
      "teacher_id": null,
      "admin_id": 1,
      "last_message_at": "2026-02-21T15:00:00.000000Z",
      "is_active": true,
      "created_at": "2026-02-21T15:00:00.000000Z",
      "updated_at": "2026-02-21T15:00:00.000000Z",
      "student": {
        "id": 5,
        "username": "student1",
        "role": "student"
      },
      "admin": {
        "id": 1,
        "username": "admin1",
        "role": "admin"
      }
    }
  }
  ```

### Send a Message in an Admin Chat

- **URL**: `/api/admin-chats/{id}/messages`
- **Method**: `POST`
- **Auth Required**: Yes (Student or Admin)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "content": "This is a message for the admin"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Message sent successfully",
    "chat_message": {
      "id": 11,
      "chat_id": 1,
      "content": "This is a message for the admin",
      "sender_id": 5,
      "sender_type": "App\\Models\\User",
      "read_at": null,
      "created_at": "2026-02-21T15:30:00.000000Z",
      "updated_at": "2026-02-21T15:30:00.000000Z",
      "sender": {
        "id": 5,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

## Configuration

The admin chat system can be configured by setting the `DEFAULT_ADMIN_ID` environment variable or by modifying the `config/chat.php` file.

### Environment Variables

- `DEFAULT_ADMIN_ID`: The ID of the admin user to use for all student-admin chats. If not specified, the system will use the first admin found in the database.

### Config File

The `config/chat.php` file contains the following configuration options:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Admin ID for Student-Admin Chats
    |--------------------------------------------------------------------------
    |
    | This value is used when a student creates a chat with an admin.
    | If not specified, the system will use the first admin found in the database.
    |
    */
    'default_admin_id' => env('DEFAULT_ADMIN_ID', null),
];
```
