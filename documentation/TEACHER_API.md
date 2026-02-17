# AB Academy Teacher API Documentation

This document provides comprehensive details about the AB Academy teacher API endpoints for frontend integration.

## Table of Contents

1. [Authentication](#authentication)
2. [Events](#events)
3. [AI Assistant](#ai-assistant)
4. [Dashboard](#dashboard)
5. [Groups](#groups)
6. [Frontend Integration Examples](#frontend-integration)

## Base URL

All API endpoints are prefixed with `/api/teacher`.

## Authentication

### Login

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
        "role": "teacher",
        "name": "Teacher Name",
        "date_joined": "2025-09-01"
      },
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    }
  }
  ```

### Logout

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

## Events

### View Event

- **URL**: `/api/live-sessions/events/view-event/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
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

### List Teacher's Events

- **URL**: `/api/teacher/events`
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
        "event_notes": "Bring your textbooks"
      },
      {
        "id": 3,
        "title": "Physics Lab",
        "type": "class",
        "event_date": "2026-03-03",
        "event_time": "10:00",
        "event_duration": 120,
        "event_organizer": 2,
        "guests": [3, 6, 7],
        "event_meet_link": "https://meet.example.com/physics-lab",
        "event_notes": "Lab equipment will be provided"
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
    "title": "Chemistry Class",
    "type": "class",
    "event_date": "2026-03-10",
    "event_time": "13:00",
    "event_duration": 90,
    "guests": [3, 4, 5],
    "event_meet_link": "https://meet.example.com/chemistry-class",
    "event_notes": "Periodic table review"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event created successfully.",
    "event": {
      "id": 4,
      "title": "Chemistry Class",
      "type": "class",
      "event_date": "2026-03-10",
      "event_time": "13:00",
      "event_duration": 90,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/chemistry-class",
      "event_notes": "Periodic table review",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

### Edit Event

- **URL**: `/api/live-sessions/events/edit-event/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes (Teacher must be the organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Chemistry Class",
    "event_notes": "Periodic table review and quiz"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Event updated successfully.",
    "event": {
      "id": 4,
      "title": "Updated Chemistry Class",
      "type": "class",
      "event_date": "2026-03-10",
      "event_time": "13:00",
      "event_duration": 90,
      "event_organizer": 2,
      "guests": [3, 4, 5],
      "event_meet_link": "https://meet.example.com/chemistry-class",
      "event_notes": "Periodic table review and quiz",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

### Invite People to Event

- **URL**: `/api/live-sessions/events/invite-people/{id}`
- **Method**: `POST`
- **Auth Required**: Yes (Teacher must be the organizer)
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "people_ids": [6, 7],
    "group_ids": [1]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "People invited successfully.",
    "event": {
      "id": 4,
      "title": "Updated Chemistry Class",
      "type": "class",
      "event_date": "2026-03-10",
      "event_time": "13:00",
      "event_duration": 90,
      "event_organizer": 2,
      "guests": [3, 4, 5, 6, 7, 8, 9],
      "event_meet_link": "https://meet.example.com/chemistry-class",
      "event_notes": "Periodic table review and quiz",
      "organizer": {
        "id": 2,
        "username": "teacher1",
        "role": "teacher"
      }
    }
  }
  ```

## AI Assistant

### Translation

- **URL**: `/api/teacher/ai-assistant/translate`
- **Method**: `POST`
- **Auth Required**: Yes
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

### Medical Translation

- **URL**: `/api/teacher/ai-assistant/translate`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "text": "The patient is experiencing acute abdominal pain.",
    "target_language": "spanish",
    "profile": "medical"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Translation successful",
    "data": {
      "translated_text": "El paciente está experimentando dolor abdominal agudo.",
      "target_language": "spanish",
      "profile": "medical",
      "model": "claude-3-5-sonnet-latest"
    }
  }
  ```

## Dashboard

### Teacher Dashboard

- **URL**: `/api/teacher/dashboard`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Welcome to the teacher dashboard",
    "data": {
      "role": "teacher",
      "name": "Teacher Name",
      "date_joined": "2025-09-01",
      "stats": {
        "total_classes": 5,
        "total_students": 15,
        "upcoming_events": 3
      }
    }
  }
  ```

## Groups

### View Teacher's Groups

- **URL**: `/api/teacher/groups`
- **Method**: `GET`
- **Auth Required**: Yes
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
        "description": "Advanced mathematics study group",
        "normal_schedule": "2026-03-01",
        "group_members": [3, 4, 5],
        "students": [
          {
            "id": 3,
            "username": "student1",
            "role": "student",
            "name": "Student One"
          },
          {
            "id": 4,
            "username": "student2",
            "role": "student",
            "name": "Student Two"
          },
          {
            "id": 5,
            "username": "student3",
            "role": "student",
            "name": "Student Three"
          }
        ]
      },
      {
        "group_id": 3,
        "group_name": "Chemistry Group",
        "description": "Chemistry study group",
        "normal_schedule": "2026-03-03",
        "group_members": [6, 7, 8],
        "students": [
          {
            "id": 6,
            "username": "student4",
            "role": "student",
            "name": "Student Four"
          },
          {
            "id": 7,
            "username": "student5",
            "role": "student",
            "name": "Student Five"
          },
          {
            "id": 8,
            "username": "student6",
            "role": "student",
            "name": "Student Six"
          }
        ]
      }
    ]
  }
  ```

### Get Group Details

- **URL**: `/api/teacher/groups/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Group retrieved successfully",
    "group": {
      "group_id": 1,
      "group_name": "Math Group",
      "description": "Advanced mathematics study group",
      "normal_schedule": "2026-03-01",
      "group_members": [3, 4, 5],
      "students": [
        {
          "id": 3,
          "username": "student1",
          "role": "student",
          "name": "Student One"
        },
        {
          "id": 4,
          "username": "student2",
          "role": "student",
          "name": "Student Two"
        },
        {
          "id": 5,
          "username": "student3",
          "role": "student",
          "name": "Student Three"
        }
      ]
    }
  }
  ```

## Frontend Integration Examples

### React Integration for Teacher Panel

```javascript
// src/services/teacher.service.js
import axios from 'axios';
import { authHeader } from './auth';

const API_URL = 'http://your-backend-url/api';
const TEACHER_API = `${API_URL}/teacher`;
const LIVE_SESSIONS_API = `${API_URL}/live-sessions`;

// Authentication
export const login = async (username, password) => {
  try {
    const response = await axios.post(`${TEACHER_API}/login`, {
      username,
      password
    });
    
    if (response.data.data.token) {
      localStorage.setItem('teacher', JSON.stringify(response.data.data));
    }
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const logout = async () => {
  try {
    const teacher = JSON.parse(localStorage.getItem('teacher'));
    
    if (teacher && teacher.token) {
      await axios.post(`${TEACHER_API}/logout`, {}, {
        headers: {
          'Authorization': `Bearer ${teacher.token}`
        }
      });
    }
    
    localStorage.removeItem('teacher');
  } catch (error) {
    console.error('Logout error:', error);
  }
};

// Dashboard
export const getDashboard = async () => {
  try {
    const response = await axios.get(`${TEACHER_API}/dashboard`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Events
export const getTeacherEvents = async () => {
  try {
    const response = await axios.get(`${TEACHER_API}/events`, {
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

// AI Assistant
export const translateText = async (translationData) => {
  try {
    const response = await axios.post(`${TEACHER_API}/ai-assistant/translate`, translationData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Groups
export const getTeacherGroups = async () => {
  try {
    const response = await axios.get(`${TEACHER_API}/groups`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getGroupDetails = async (id) => {
  try {
    const response = await axios.get(`${TEACHER_API}/groups/${id}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

### Example React Component for Translation

```jsx
// src/components/Translation/TranslationForm.jsx
import React, { useState } from 'react';
import { translateText } from '../../services/teacher.service';

const TranslationForm = () => {
  const [text, setText] = useState('');
  const [targetLanguage, setTargetLanguage] = useState('spanish');
  const [profile, setProfile] = useState('normal');
  const [translatedText, setTranslatedText] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      setError(null);
      const response = await translateText({
        text,
        target_language: targetLanguage,
        profile
      });
      setTranslatedText(response.data.translated_text);
      setLoading(false);
    } catch (err) {
      setError(err.message || 'Translation failed');
      setLoading(false);
    }
  };

  return (
    <div className="translation-form">
      <h2>AI Translation Assistant</h2>
      {error && <div className="error">{error}</div>}
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="text">Text to Translate:</label>
          <textarea
            id="text"
            value={text}
            onChange={(e) => setText(e.target.value)}
            required
            rows={5}
          />
        </div>
        
        <div className="form-group">
          <label htmlFor="targetLanguage">Target Language:</label>
          <select
            id="targetLanguage"
            value={targetLanguage}
            onChange={(e) => setTargetLanguage(e.target.value)}
          >
            <option value="spanish">Spanish</option>
            <option value="french">French</option>
            <option value="german">German</option>
            <option value="italian">Italian</option>
            <option value="dutch">Dutch</option>
            <option value="portuguese">Portuguese</option>
            <option value="russian">Russian</option>
            <option value="chinese">Chinese</option>
            <option value="japanese">Japanese</option>
          </select>
        </div>
        
        <div className="form-group">
          <label htmlFor="profile">Translation Profile:</label>
          <select
            id="profile"
            value={profile}
            onChange={(e) => setProfile(e.target.value)}
          >
            <option value="normal">Normal</option>
            <option value="medical">Medical</option>
          </select>
        </div>
        
        <button type="submit" disabled={loading}>
          {loading ? 'Translating...' : 'Translate'}
        </button>
      </form>
      
      {translatedText && (
        <div className="translation-result">
          <h3>Translation Result:</h3>
          <div className="translated-text">{translatedText}</div>
        </div>
      )}
    </div>
  );
};

export default TranslationForm;
```
