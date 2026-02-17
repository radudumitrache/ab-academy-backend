# AB Academy Student API Documentation

This document provides comprehensive details about the AB Academy student API endpoints for frontend integration.

## Table of Contents

1. [Authentication](#authentication)
2. [Events](#events)
3. [Dashboard](#dashboard)
4. [Chats](#chats)
5. [Notifications](#notifications)
6. [Frontend Integration Examples](#frontend-integration)

## Base URL

All API endpoints are prefixed with `/api/student`.

## Authentication

### Login

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
        "role": "student",
        "name": "Student Name",
        "birthday": "2000-01-01"
      },
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
  }
  ```

### Logout

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

### View Event

- **URL**: `/api/live-sessions/events/view-event/{id}`
- **Method**: `GET`
- **Auth Required**: Yes (Student must be in guest list)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Event retrieved successfully.",
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

### List Student's Events

- **URL**: `/api/student/events`
- **Method**: `GET`
- **Auth Required**: Yes
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
      },
      {
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
    ]
  }
  ```

### Create Event

- **URL**: `/api/live-sessions/events/create-event`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Study Group",
    "type": "meeting",
    "event_date": "2026-03-15",
    "event_time": "18:00",
    "event_duration": 60,
    "guests": [4, 5],
    "event_meet_link": "https://meet.example.com/study-group-2",
    "event_notes": "Math exam preparation"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event created successfully.",
    "event": {
      "id": 5,
      "title": "Study Group",
      "type": "meeting",
      "event_date": "2026-03-15",
      "event_time": "18:00",
      "event_duration": 60,
      "event_organizer": 3,
      "guests": [4, 5],
      "event_meet_link": "https://meet.example.com/study-group-2",
      "event_notes": "Math exam preparation",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

### Edit Event

- **URL**: `/api/live-sessions/events/edit-event/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Student must be the organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Study Group",
    "event_notes": "Math and Physics exam preparation"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event updated successfully.",
    "event": {
      "id": 5,
      "title": "Updated Study Group",
      "type": "meeting",
      "event_date": "2026-03-15",
      "event_time": "18:00",
      "event_duration": 60,
      "event_organizer": 3,
      "guests": [4, 5],
      "event_meet_link": "https://meet.example.com/study-group-2",
      "event_notes": "Math and Physics exam preparation",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

### Invite People to Event

- **URL**: `/api/live-sessions/events/invite-people/{id}`
- **Method**: `POST`
- **Auth Required**: Yes (Student must be the organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "people_ids": [6, 7]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "People invited successfully.",
    "event": {
      "id": 5,
      "title": "Updated Study Group",
      "type": "meeting",
      "event_date": "2026-03-15",
      "event_time": "18:00",
      "event_duration": 60,
      "event_organizer": 3,
      "guests": [4, 5, 6, 7],
      "event_meet_link": "https://meet.example.com/study-group-2",
      "event_notes": "Math and Physics exam preparation",
      "organizer": {
        "id": 3,
        "username": "student1",
        "role": "student"
      }
    }
  }
  ```

## Dashboard

### Student Dashboard

- **URL**: `/api/student/dashboard`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Welcome to the student dashboard",
    "data": {
      "role": "student",
      "name": "Student Name",
      "birthday": "2000-01-01",
      "stats": {
        "total_classes": 3,
        "total_groups": 2,
        "upcoming_events": 2
      }
    }
  }
  ```

## Chats

### List Chats

- **URL**: `/api/student/chats`
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
        "student_recipient": 3,
        "date_created": "2026-02-15",
        "messages": [
          {
            "id": 1,
            "message_author": 1,
            "message_text": "Hello, how are your studies going?",
            "created_at": "2026-02-15T10:00:00Z"
          },
          {
            "id": 2,
            "message_author": 3,
            "message_text": "Going well, thank you!",
            "created_at": "2026-02-15T10:05:00Z"
          }
        ]
      },
      {
        "id": 2,
        "student_recipient": 3,
        "date_created": "2026-02-17",
        "messages": [
          {
            "id": 3,
            "message_author": 1,
            "message_text": "Don't forget about tomorrow's exam.",
            "created_at": "2026-02-17T14:00:00Z"
          }
        ]
      }
    ]
  }
  ```

### Get Chat Details

- **URL**: `/api/student/chats/{id}`
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
      "student_recipient": 3,
      "date_created": "2026-02-15",
      "messages": [
        {
          "id": 1,
          "message_author": 1,
          "message_text": "Hello, how are your studies going?",
          "created_at": "2026-02-15T10:00:00Z"
        },
        {
          "id": 2,
          "message_author": 3,
          "message_text": "Going well, thank you!",
          "created_at": "2026-02-15T10:05:00Z"
        }
      ]
    }
  }
  ```

### Send Message

- **URL**: `/api/student/chats/{id}/messages`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "message_text": "I have a question about tomorrow's class."
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Message sent successfully",
    "chat_message": {
      "id": 4,
      "message_author": 3,
      "message_text": "I have a question about tomorrow's class.",
      "created_at": "2026-02-17T15:30:00Z"
    }
  }
  ```

## Notifications

### List Notifications

- **URL**: `/api/student/notifications`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Notifications retrieved successfully",
    "notifications": [
      {
        "id": 1,
        "notification_owner": 3,
        "notification_message": "You have been invited to a new event: Math Class",
        "notification_time": "2026-02-15T09:00:00Z",
        "is_seen": true
      },
      {
        "id": 2,
        "notification_owner": 3,
        "notification_message": "New message from Admin",
        "notification_time": "2026-02-17T14:00:00Z",
        "is_seen": false
      }
    ]
  }
  ```

### Mark Notification as Seen

- **URL**: `/api/student/notifications/{id}/mark-seen`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Notification marked as seen",
    "notification": {
      "id": 2,
      "notification_owner": 3,
      "notification_message": "New message from Admin",
      "notification_time": "2026-02-17T14:00:00Z",
      "is_seen": true
    }
  }
  ```

## Frontend Integration Examples

### React Integration for Student Panel

```javascript
// src/services/student.service.js
import axios from 'axios';
import { authHeader } from './auth';

const API_URL = 'http://your-backend-url/api';
const STUDENT_API = `${API_URL}/student`;
const LIVE_SESSIONS_API = `${API_URL}/live-sessions`;

// Authentication
export const login = async (username, password) => {
  try {
    const response = await axios.post(`${STUDENT_API}/login`, {
      username,
      password
    });
    
    if (response.data.data.token) {
      localStorage.setItem('student', JSON.stringify(response.data.data));
    }
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const logout = async () => {
  try {
    const student = JSON.parse(localStorage.getItem('student'));
    
    if (student && student.token) {
      await axios.post(`${STUDENT_API}/logout`, {}, {
        headers: {
          'Authorization': `Bearer ${student.token}`
        }
      });
    }
    
    localStorage.removeItem('student');
  } catch (error) {
    console.error('Logout error:', error);
  }
};

// Dashboard
export const getDashboard = async () => {
  try {
    const response = await axios.get(`${STUDENT_API}/dashboard`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Events
export const getStudentEvents = async () => {
  try {
    const response = await axios.get(`${STUDENT_API}/events`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createEvent = async (eventData) => {
  try {
    const response = await axios.post(`${LIVE_SESSIONS_API}/events/create-event`, eventData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const updateEvent = async (id, eventData) => {
  try {
    const response = await axios.put(`${LIVE_SESSIONS_API}/events/edit-event/${id}`, eventData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const invitePeople = async (id, inviteData) => {
  try {
    const response = await axios.post(`${LIVE_SESSIONS_API}/events/invite-people/${id}`, inviteData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const viewEvent = async (id) => {
  try {
    const response = await axios.get(`${LIVE_SESSIONS_API}/events/view-event/${id}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Chats
export const getChats = async () => {
  try {
    const response = await axios.get(`${STUDENT_API}/chats`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getChatDetails = async (id) => {
  try {
    const response = await axios.get(`${STUDENT_API}/chats/${id}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const sendMessage = async (chatId, messageText) => {
  try {
    const response = await axios.post(`${STUDENT_API}/chats/${chatId}/messages`, {
      message_text: messageText
    }, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Notifications
export const getNotifications = async () => {
  try {
    const response = await axios.get(`${STUDENT_API}/notifications`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const markNotificationSeen = async (id) => {
  try {
    const response = await axios.put(`${STUDENT_API}/notifications/${id}/mark-seen`, {}, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

### Example React Component for Student Dashboard

```jsx
// src/components/Dashboard/StudentDashboard.jsx
import React, { useState, useEffect } from 'react';
import { getDashboard, getStudentEvents, getNotifications } from '../../services/student.service';

const StudentDashboard = () => {
  const [dashboard, setDashboard] = useState(null);
  const [events, setEvents] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        setError(null);
        
        // Fetch dashboard data
        const dashboardResponse = await getDashboard();
        setDashboard(dashboardResponse.data);
        
        // Fetch events
        const eventsResponse = await getStudentEvents();
        setEvents(eventsResponse.events);
        
        // Fetch notifications
        const notificationsResponse = await getNotifications();
        setNotifications(notificationsResponse.notifications);
        
        setLoading(false);
      } catch (err) {
        setError(err.message || 'Failed to fetch dashboard data');
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (loading) return <div>Loading dashboard...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!dashboard) return <div>No dashboard data available</div>;

  return (
    <div className="student-dashboard">
      <h1>Welcome, {dashboard.name}!</h1>
      
      <div className="dashboard-stats">
        <h2>Your Stats</h2>
        <div className="stats-grid">
          <div className="stat-card">
            <h3>Classes</h3>
            <p>{dashboard.stats.total_classes}</p>
          </div>
          <div className="stat-card">
            <h3>Groups</h3>
            <p>{dashboard.stats.total_groups}</p>
          </div>
          <div className="stat-card">
            <h3>Upcoming Events</h3>
            <p>{dashboard.stats.upcoming_events}</p>
          </div>
        </div>
      </div>
      
      <div className="upcoming-events">
        <h2>Upcoming Events</h2>
        {events.length === 0 ? (
          <p>No upcoming events</p>
        ) : (
          <ul className="event-list">
            {events.map(event => (
              <li key={event.id} className="event-card">
                <h3>{event.title}</h3>
                <p>Date: {event.event_date} at {event.event_time}</p>
                <p>Duration: {event.event_duration} minutes</p>
                <p>Type: {event.type}</p>
                <a href={event.event_meet_link} target="_blank" rel="noopener noreferrer">
                  Join Meeting
                </a>
              </li>
            ))}
          </ul>
        )}
      </div>
      
      <div className="notifications">
        <h2>Notifications</h2>
        {notifications.length === 0 ? (
          <p>No notifications</p>
        ) : (
          <ul className="notification-list">
            {notifications.map(notification => (
              <li 
                key={notification.id} 
                className={`notification-item ${!notification.is_seen ? 'unread' : ''}`}
              >
                <p>{notification.notification_message}</p>
                <small>
                  {new Date(notification.notification_time).toLocaleString()}
                </small>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
};

export default StudentDashboard;
```
