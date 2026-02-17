# AB Academy API Documentation

This document provides comprehensive details about the AB Academy backend API endpoints for frontend integration.

## Table of Contents

1. [Authentication](#authentication)
2. [Events](#events)
3. [Groups](#groups)
4. [AI Assistant](#ai-assistant)
5. [User Management](#user-management)
6. [Dashboard](#dashboard)
7. [Notifications](#notifications)
8. [Chats & Messages](#chats-messages)
9. [Frontend Integration Examples](#frontend-integration)

## Base URL

All API endpoints are prefixed with `/api`.

## Authentication

Authentication is handled using OAuth 2.0 with Laravel Passport. All protected endpoints require a valid Bearer token in the Authorization header.

### Admin Authentication

#### Login

- **URL**: `/api/admin/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "admin_username",
    "password": "admin_password"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Admin logged in successfully",
    "data": {
      "user": {
        "id": 1,
        "username": "admin_username",
        "role": "admin"
      },
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
  }
  ```

#### Logout

- **URL**: `/api/admin/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Admin logged out successfully"
  }
  ```

### Teacher Authentication

#### Login

- **URL**: `/api/teacher/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "teacher_username",
    "password": "teacher_password"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher logged in successfully",
    "data": {
      "user": {
        "id": 2,
        "username": "teacher_username",
        "role": "teacher"
      },
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
  }
  ```

#### Logout

- **URL**: `/api/teacher/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher logged out successfully"
  }
  ```

### Student Authentication

#### Login

- **URL**: `/api/student/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "student_username",
    "password": "student_password"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student logged in successfully",
    "data": {
      "user": {
        "id": 3,
        "username": "student_username",
        "role": "student"
      },
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
  }
  ```

#### Logout

- **URL**: `/api/student/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student logged out successfully"
  }
  ```

## Events

Events can be managed through both admin routes and live session routes. The following endpoints are available:

### Admin Event Management

#### List All Events

- **URL**: `/api/admin/events`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Events retrieved successfully",
    "events": [
      {
        "id": 1,
        "title": "Math Class",
        "type": "class",
        "event_date": "2026-03-01",
        "event_time": "14:00",
        "event_duration": 60,
        "event_organizer": 2,
        "guests": [3, 4, 5],
        "event_meet_link": "https://meet.example.com/math-class",
        "event_notes": "Bring your textbooks",
        "organizer": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        }
      }
    ]
  }
  ```

#### Create Event (Admin)

- **URL**: `/api/admin/events`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Math Class",
    "type": "class",
    "event_date": "2026-03-01",
    "event_time": "14:00",
    "event_duration": 60,
    "event_organizer": 2,
    "guests": [3, 4, 5],
    "event_meet_link": "https://meet.example.com/math-class",
    "event_notes": "Bring your textbooks"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event created successfully",
    "event": {
      "id": 1,
      "title": "Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Bring your textbooks",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

#### Get Event Details (Admin)

- **URL**: `/api/admin/events/{id}`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event retrieved successfully",
    "event": {
      "id": 1,
      "title": "Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Bring your textbooks",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

#### Update Event (Admin)

- **URL**: `/api/admin/events/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Math Class",
    "event_notes": "Updated notes"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event updated successfully",
    "event": {
      "id": 1,
      "title": "Updated Math Class",
      "type": "class",
      "event_date": "2026-03-01",
      "event_time": "14:00",
      "event_duration": 60,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/math-class",
      "event_notes": "Updated notes",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

#### Delete Event (Admin)

- **URL**: `/api/admin/events/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event deleted successfully"
  }
  ```

### Live Session Event Management

#### Create Event

- **URL**: `/api/live-sessions/events/create-event`
- **Method**: `POST`
- **Auth Required**: Yes (Admin or Student)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Study Group",
    "type": "meeting",
    "event_date": "2026-03-05",
    "event_time": "16:00",
    "event_duration": 90,
    "guests": [3, 4, 5],
    "event_meet_link": "https://meet.example.com/study-group",
    "event_notes": "Preparing for exams"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event created successfully.",
    "event": {
      "id": 2,
      "title": "Study Group",
      "type": "meeting",
      "event_date": "2026-03-05",
      "event_time": "16:00",
      "event_duration": 90,
      "event_organizer": 3,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/study-group",
      "event_notes": "Preparing for exams",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

#### Edit Event

- **URL**: `/api/live-sessions/events/edit-event/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin or Event Organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Study Group",
    "event_notes": "Updated study notes"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event updated successfully.",
    "event": {
      "id": 2,
      "title": "Updated Study Group",
      "type": "meeting",
      "event_date": "2026-03-05",
      "event_time": "16:00",
      "event_duration": 90,
      "event_organizer": 3,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/study-group",
      "event_notes": "Updated study notes",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

#### View Event

- **URL**: `/api/live-sessions/events/view-event/{id}`
- **Method**: `GET`
- **Auth Required**: Yes (Admin, Teacher, or Student in guest list)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event retrieved successfully.",
    "event": {
      "id": 2,
      "title": "Updated Study Group",
      "type": "meeting",
      "event_date": "2026-03-05",
      "event_time": "16:00",
      "event_duration": 90,
      "event_organizer": 3,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/study-group",
      "event_notes": "Updated study notes",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

#### Invite People to Event

- **URL**: `/api/live-sessions/events/invite-people/{id}`
- **Method**: `POST`
- **Auth Required**: Yes (Admin or Event Organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "people_ids": [6, 7],
    "group_ids": [1, 2]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "People invited successfully.",
    "event": {
      "id": 2,
      "title": "Updated Study Group",
      "type": "meeting",
      "event_date": "2026-03-05",
      "event_time": "16:00",
      "event_duration": 90,
      "event_organizer": 3,
      "guests": [3, 4, 5, 6, 7, 8, 9, 10],
      "event_meet_link": "https://meet.example.com/study-group",
      "event_notes": "Updated study notes",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```
