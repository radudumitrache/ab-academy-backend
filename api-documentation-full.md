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

## Groups

Groups can be managed through admin routes. The following endpoints are available:

### List All Groups

- **URL**: `/api/admin/groups`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Groups retrieved successfully",
    "groups": [
      {
        "group_id": 1,
        "group_name": "Math Group",
        "group_teacher": 2,
        "description": "Advanced mathematics study group",
        "normal_schedule": "2026-03-01",
        "group_members": [3, 4, 5],
        "teacher": {
          "id": 2,
          "username": "teacher1",
          "role": "teacher"
        },
        "students": [
          {
            "id": 3,
            "username": "student1",
            "role": "student"
          },
          {
            "id": 4,
            "username": "student2",
            "role": "student"
          },
          {
            "id": 5,
            "username": "student3",
            "role": "student"
          }
        ]
      }
    ]
  }
  ```

### Create Group

- **URL**: `/api/admin/groups`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "group_name": "Science Group",
    "group_teacher": 2,
    "description": "Physics and chemistry study group",
    "normal_schedule": "2026-03-02",
    "group_members": [3, 4, 5]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group created successfully",
    "group": {
      "group_id": 2,
      "group_name": "Science Group",
      "group_teacher": 2,
      "description": "Physics and chemistry study group",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        }
      ]
    }
  }
  ```

### Get Group Details

- **URL**: `/api/admin/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group retrieved successfully",
    "group": {
      "group_id": 2,
      "group_name": "Science Group",
      "group_teacher": 2,
      "description": "Physics and chemistry study group",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        }
      ]
    }
  }
  ```

### Update Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "group_name": "Updated Science Group",
    "description": "Updated description"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group updated successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        }
      ]
    }
  }
  ```

### Delete Group

- **URL**: `/api/admin/groups/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group deleted successfully"
  }
  ```

### Add Student to Group

- **URL**: `/api/admin/groups/{id}/students`
- **Method**: `POST`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "student_id": 6
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student added to group successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5, 6],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        },
        {
          "id": 6,
          "username": "student4",
          "role": "student"
        }
      ]
    }
  }
  ```

### Remove Student from Group

- **URL**: `/api/admin/groups/{groupId}/students/{studentId}`
- **Method**: `DELETE`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student removed from group successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 5],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student"
        }
      ]
    }
  }
  ```

### Update Group Members

- **URL**: `/api/admin/groups/{id}/group-members`
- **Method**: `PUT`
- **Auth Required**: Yes (Admin only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "group_members": [3, 4, 7, 8]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Group members updated successfully",
    "group": {
      "group_id": 2,
      "group_name": "Updated Science Group",
      "group_teacher": 2,
      "description": "Updated description",
      "normal_schedule": "2026-03-02",
      "group_members": [3, 4, 7, 8],
      "teacher": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      },
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student"
        },
        {
          "id": 7,
          "username": "student5",
          "role": "student"
        },
        {
          "id": 8,
          "username": "student6",
          "role": "student"
        }
      ]
    }
  }
  ```

## AI Assistant

### Translation

- **URL**: `/api/teacher/ai-assistant/translate`
- **Method**: `POST`
- **Auth Required**: Yes (Teacher only)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "text": "This is the text to be translated.",
    "target_language": "dutch",
    "profile": "normal"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Translation successful",
    "data": {
      "translated_text": "Dit is de tekst die vertaald moet worden.",
      "target_language": "dutch",
      "profile": "normal",
      "model": "claude-3-5-sonnet-latest"
    }
  }
  ```

## Frontend Integration Examples

### React Integration

Here's how to connect your React frontend to the AB Academy API:

#### Setting Up Authentication

```javascript
// src/services/auth.js
import axios from 'axios';

const API_URL = 'http://your-backend-url/api';

export const login = async (username, password, role) => {
  try {
    const response = await axios.post(`${API_URL}/${role}/login`, {
      username,
      password
    });
    
    if (response.data.data.token) {
      localStorage.setItem('user', JSON.stringify(response.data.data));
    }
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const logout = async (role) => {
  try {
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (user && user.token) {
      await axios.post(`${API_URL}/${role}/logout`, {}, {
        headers: {
          'Authorization': `Bearer ${user.token}`
        }
      });
    }
    
    localStorage.removeItem('user');
  } catch (error) {
    console.error('Logout error:', error);
  }
};

export const getCurrentUser = () => {
  return JSON.parse(localStorage.getItem('user'));
};

export const authHeader = () => {
  const user = JSON.parse(localStorage.getItem('user'));
  
  if (user && user.token) {
    return { 'Authorization': `Bearer ${user.token}` };
  } else {
    return {};
  }
};
```

#### API Service Example

```javascript
// src/services/event.service.js
import axios from 'axios';
import { authHeader } from './auth';

const API_URL = 'http://your-backend-url/api';

export const getEvents = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/events`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createEvent = async (eventData) => {
  try {
    const response = await axios.post(`${API_URL}/live-sessions/events/create-event`, eventData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const updateEvent = async (id, eventData) => {
  try {
    const response = await axios.put(`${API_URL}/live-sessions/events/edit-event/${id}`, eventData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const invitePeople = async (id, inviteData) => {
  try {
    const response = await axios.post(`${API_URL}/live-sessions/events/invite-people/${id}`, inviteData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

#### React Component Example

```jsx
// src/components/Events/EventList.jsx
import React, { useState, useEffect } from 'react';
import { getEvents } from '../../services/event.service';

const EventList = () => {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchEvents = async () => {
      try {
        setLoading(true);
        const response = await getEvents();
        setEvents(response.events);
        setLoading(false);
      } catch (err) {
        setError(err.message || 'Failed to fetch events');
        setLoading(false);
      }
    };

    fetchEvents();
  }, []);

  if (loading) return <div>Loading events...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="event-list">
      <h2>Events</h2>
      {events.length === 0 ? (
        <p>No events found</p>
      ) : (
        <ul>
          {events.map(event => (
            <li key={event.id}>
              <h3>{event.title}</h3>
              <p>Date: {event.event_date} at {event.event_time}</p>
              <p>Duration: {event.event_duration} minutes</p>
              <p>Type: {event.type}</p>
              <p>Organizer: {event.organizer.username}</p>
              <p>Notes: {event.event_notes}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default EventList;
```
