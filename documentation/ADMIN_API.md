# AB Academy Admin API Documentation

This document provides comprehensive details about the AB Academy admin API endpoints for frontend integration.

## Table of Contents

1. [Authentication](#authentication)
2. [Events](#events)
3. [Groups](#groups)
4. [User Management](#user-management)
5. [Dashboard](#dashboard)
6. [Frontend Integration Examples](#frontend-integration)

## Base URL

All API endpoints are prefixed with `/api/admin`.

## Authentication

### Login

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

### Logout

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

## Events

### List All Events

- **URL**: `/api/admin/events`
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
      }
    ]
  }
  ```

### Create Event

- **URL**: `/api/admin/events`
- **Method**: `POST`
- **Auth Required**: Yes
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

### Get Event Details

- **URL**: `/api/admin/events/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
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

### Update Event

- **URL**: `/api/admin/events/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
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

### Delete Event

- **URL**: `/api/admin/events/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
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

## Groups

### List All Groups

- **URL**: `/api/admin/groups`
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
- **Auth Required**: Yes
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
- **Auth Required**: Yes
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
- **Auth Required**: Yes
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
- **Auth Required**: Yes
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
- **Auth Required**: Yes
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
- **Auth Required**: Yes
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

## User Management

### Create Student

- **URL**: `/api/admin/students`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "new_student",
    "password": "password123",
    "name": "New Student",
    "birthday": "2000-01-01"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Student created successfully",
    "student": {
      "id": 6,
      "username": "new_student",
      "role": "student",
      "name": "New Student",
      "birthday": "2000-01-01"
    }
  }
  ```

### List Students

- **URL**: `/api/admin/students`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Students retrieved successfully",
    "students": [
      {
        "id": 3,
        "username": "student1",
        "role": "student",
        "name": "Student One",
        "birthday": "2001-05-15"
      },
      {
        "id": 4,
        "username": "student2",
        "role": "student",
        "name": "Student Two",
        "birthday": "2002-03-22"
      }
    ]
  }
  ```

### Delete Student

- **URL**: `/api/admin/students/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student deleted successfully"
  }
  ```

### Create Teacher

- **URL**: `/api/admin/teachers`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "username": "new_teacher",
    "password": "password123",
    "name": "New Teacher",
    "date_joined": "2026-01-15"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher created successfully",
    "teacher": {
      "id": 5,
      "username": "new_teacher",
      "role": "teacher",
      "name": "New Teacher",
      "date_joined": "2026-01-15"
    }
  }
  ```

### List Teachers

- **URL**: `/api/admin/teachers`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teachers retrieved successfully",
    "teachers": [
      {
        "id": 2,
        "username": "teacher1",
        "role": "teacher",
        "name": "Teacher One",
        "date_joined": "2025-09-01"
      },
      {
        "id": 5,
        "username": "new_teacher",
        "role": "teacher",
        "name": "New Teacher",
        "date_joined": "2026-01-15"
      }
    ]
  }
  ```

### Delete Teacher

- **URL**: `/api/admin/teachers/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Teacher deleted successfully"
  }
  ```

## Dashboard

### Admin Dashboard

- **URL**: `/api/admin/dashboard`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Welcome to the admin dashboard",
    "data": {
      "role": "admin",
      "stats": {
        "total_students": 10,
        "total_teachers": 5,
        "total_groups": 3,
        "total_events": 8
      }
    }
  }
  ```

## Frontend Integration Examples

### React Integration for Admin Panel

```javascript
// src/services/admin.service.js
import axios from 'axios';
import { authHeader } from './auth';

const API_URL = 'http://your-backend-url/api/admin';

// Authentication
export const login = async (username, password) => {
  try {
    const response = await axios.post(`${API_URL}/login`, {
      username,
      password
    });
    
    if (response.data.data.token) {
      localStorage.setItem('admin', JSON.stringify(response.data.data));
    }
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const logout = async () => {
  try {
    const admin = JSON.parse(localStorage.getItem('admin'));
    
    if (admin && admin.token) {
      await axios.post(`${API_URL}/logout`, {}, {
        headers: {
          'Authorization': `Bearer ${admin.token}`
        }
      });
    }
    
    localStorage.removeItem('admin');
  } catch (error) {
    console.error('Logout error:', error);
  }
};

// Events
export const getEvents = async () => {
  try {
    const response = await axios.get(`${API_URL}/events`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createEvent = async (eventData) => {
  try {
    const response = await axios.post(`${API_URL}/events`, eventData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Groups
export const getGroups = async () => {
  try {
    const response = await axios.get(`${API_URL}/groups`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createGroup = async (groupData) => {
  try {
    const response = await axios.post(`${API_URL}/groups`, groupData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// User Management
export const getStudents = async () => {
  try {
    const response = await axios.get(`${API_URL}/students`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createStudent = async (studentData) => {
  try {
    const response = await axios.post(`${API_URL}/students`, studentData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getTeachers = async () => {
  try {
    const response = await axios.get(`${API_URL}/teachers`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const createTeacher = async (teacherData) => {
  try {
    const response = await axios.post(`${API_URL}/teachers`, teacherData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Dashboard
export const getDashboard = async () => {
  try {
    const response = await axios.get(`${API_URL}/dashboard`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```
