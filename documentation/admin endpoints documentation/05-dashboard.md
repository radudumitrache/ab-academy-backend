# Dashboard

This section covers the API endpoints for the admin dashboard in the AB Academy platform.

## Get Dashboard Data

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
    "message": "Dashboard data retrieved successfully",
    "data": {
      "total_students": 25,
      "total_teachers": 8,
      "total_groups": 5,
      "total_courses": 12,
      "recent_activities": [
        {
          "id": 1,
          "action": "create",
          "model": "Group",
          "model_id": 5,
          "user_id": 1,
          "user_role": "admin",
          "description": "Created new group 'Physics Group'",
          "created_at": "2026-02-19T14:30:00.000000Z"
        },
        {
          "id": 2,
          "action": "update",
          "model": "Student",
          "model_id": 12,
          "user_id": 1,
          "user_role": "admin",
          "description": "Updated student information",
          "created_at": "2026-02-19T13:45:00.000000Z"
        }
      ],
      "upcoming_events": [
        {
          "id": 3,
          "title": "Math Exam",
          "type": "exam",
          "event_date": "2026-02-25",
          "event_time": "10:00",
          "event_duration": 120
        },
        {
          "id": 4,
          "title": "Science Workshop",
          "type": "workshop",
          "event_date": "2026-02-26",
          "event_time": "14:00",
          "event_duration": 180
        }
      ]
    }
  }
  ```

## Get Key Performance Indicators (KPIs)

- **URL**: `/api/admin/dashboard/kpis`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "KPIs retrieved successfully",
    "kpis": {
      "student_growth": {
        "current_month": 5,
        "previous_month": 3,
        "growth_percentage": 66.67
      },
      "teacher_growth": {
        "current_month": 2,
        "previous_month": 1,
        "growth_percentage": 100
      },
      "course_completion_rate": 85.5,
      "average_exam_score": 78.2,
      "active_groups": 5
    }
  }
  ```

## Get Recent Activities

- **URL**: `/api/admin/dashboard/activities`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters**:
  - `limit` (optional): Number of activities to return (default: 10)
- **Success Response**:
  ```json
  {
    "message": "Activities retrieved successfully",
    "activities": [
      {
        "id": 1,
        "action": "create",
        "model": "Group",
        "model_id": 5,
        "user_id": 1,
        "user_role": "admin",
        "description": "Created new group 'Physics Group'",
        "changes": {
          "group_name": "Physics Group",
          "group_teacher": 3,
          "description": "Advanced physics study group"
        },
        "created_at": "2026-02-19T14:30:00.000000Z"
      },
      {
        "id": 2,
        "action": "update",
        "model": "Student",
        "model_id": 12,
        "user_id": 1,
        "user_role": "admin",
        "description": "Updated student information",
        "changes": {
          "email": "new_email@example.com",
          "telephone": "+9876543210"
        },
        "created_at": "2026-02-19T13:45:00.000000Z"
      }
    ]
  }
  ```

## Get Chat Logs

- **URL**: `/api/admin/dashboard/chat-logs`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters**:
  - `limit` (optional): Number of chat logs to return (default: 20)
  - `student_id` (optional): Filter logs by student ID
  - `admin_id` (optional): Filter logs by admin ID
  - `from_date` (optional): Filter logs from this date (format: YYYY-MM-DD)
  - `to_date` (optional): Filter logs to this date (format: YYYY-MM-DD)
- **Success Response**:
  ```json
  {
    "message": "Chat logs retrieved successfully",
    "logs": [
      {
        "id": 1,
        "student_id": 5,
        "admin_id": 1,
        "message": "Hello, I need help with my course registration.",
        "sender_type": "student",
        "read": true,
        "created_at": "2026-02-20T09:30:00.000000Z",
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
      },
      {
        "id": 2,
        "student_id": 5,
        "admin_id": 1,
        "message": "Sure, I can help you with that. What course are you trying to register for?",
        "sender_type": "admin",
        "read": true,
        "created_at": "2026-02-20T09:32:00.000000Z",
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
    ],
    "total": 2,
    "unread": 0
  }
  ```

## Frontend Integration Example

```javascript
import axios from 'axios';

const API_URL = 'https://api.abacademy.com/api';

const authHeader = () => {
  const token = localStorage.getItem('admin_token');
  return { Authorization: `Bearer ${token}` };
};

export const getDashboardData = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/dashboard`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getDashboardKPIs = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/dashboard/kpis`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getDashboardActivities = async (limit = 10) => {
  try {
    const response = await axios.get(`${API_URL}/admin/dashboard/activities`, {
      headers: authHeader(),
      params: { limit }
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getChatLogs = async (params = {}) => {
  try {
    const response = await axios.get(`${API_URL}/admin/dashboard/chat-logs`, {
      headers: authHeader(),
      params
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

---

# Search

## Search

- **URL**: `/api/admin/dashboard/search`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters**:
  - `query` (required): Search term, minimum 2 characters
  - `type` (optional): Scope of the search. One of `all`, `users`, `events`, `groups` (default: `all`)
  - `limit` (optional): Max results to return, between 1 and 50 (default: `10`)
- **How matching works**: Three-tier matching against usernames, event titles, and group names, in priority order:
  1. **Exact match** → relevance `100`
  2. **Contains match** (field contains the query as a substring) → relevance `85`
  3. **Fuzzy match** (Levenshtein distance up to 50% of the query length) → relevance `10–75`

  Results are sorted by relevance descending.
- **Success Response** (`type=all`):
  ```json
  {
    "message": "Search results retrieved successfully",
    "query": "john",
    "count": 3,
    "results": [
      {
        "id": 5,
        "name": "john_doe",
        "email": "john@example.com",
        "type": "user",
        "role": "student",
        "relevance": 75
      },
      {
        "id": 2,
        "name": "Johnson Group",
        "description": "Advanced math group",
        "type": "group",
        "relevance": 50
      },
      {
        "id": 7,
        "name": "Johns Workshop",
        "type": "event",
        "event_type": "workshop",
        "event_date": "2026-03-10",
        "relevance": 50
      }
    ]
  }
  ```
- **Success Response** (`type=users`):
  ```json
  {
    "message": "Search results retrieved successfully",
    "query": "john",
    "count": 1,
    "results": [
      {
        "id": 5,
        "name": "john_doe",
        "email": "john@example.com",
        "type": "user",
        "role": "student",
        "relevance": 75
      }
    ]
  }
  ```
- **Error Response** (missing or too-short query):
  ```json
  {
    "message": "The query field is required.",
    "errors": {
      "query": ["The query field must be at least 2 characters."]
    }
  }
  ```
  HTTP Status: `422`

## Frontend Integration Example

```javascript
export const search = async (query, type = 'all', limit = 10) => {
  try {
    const response = await axios.get(`${API_URL}/admin/dashboard/search`, {
      headers: authHeader(),
      params: { query, type, limit }
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

---

# Database Logs

This section covers the API endpoints for retrieving audit logs of all actions performed in the platform.

## Get All Logs

- **URL**: `/api/admin/logs`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Query Parameters**:
  - `action` (optional): Filter by action type (`created`, `updated`, `deleted`)
  - `model` (optional): Filter by model name (partial match, e.g. `Group`, `Student`)
  - `user_id` (optional): Filter by the ID of the user who performed the action
  - `per_page` (optional): Number of logs per page (default: `50`)
- **Success Response**:
  ```json
  {
    "message": "Database logs retrieved successfully",
    "logs": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "action": "created",
          "model": "Group",
          "model_id": 5,
          "user_id": 1,
          "user_role": "admin",
          "description": "Admin created a new group",
          "changes": {
            "group_name": "Physics Group",
            "group_teacher": 3,
            "description": "Advanced physics study group"
          },
          "created_at": "2026-02-19T14:30:00.000000Z"
        },
        {
          "id": 2,
          "action": "updated",
          "model": "Student",
          "model_id": 12,
          "user_id": 1,
          "user_role": "admin",
          "description": "Admin updated student",
          "changes": {
            "old": { "email": "old@example.com" },
            "new": { "email": "new@example.com" }
          },
          "created_at": "2026-02-19T13:45:00.000000Z"
        }
      ],
      "per_page": 50,
      "total": 2,
      "last_page": 1,
      "from": 1,
      "to": 2
    }
  }
  ```

## Get Single Log

- **URL**: `/api/admin/logs/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the log entry
- **Success Response**:
  ```json
  {
    "message": "Database log retrieved successfully",
    "log": {
      "id": 1,
      "action": "created",
      "model": "Group",
      "model_id": 5,
      "user_id": 1,
      "user_role": "admin",
      "description": "Admin created a new group",
      "changes": {
        "group_name": "Physics Group",
        "group_teacher": 3,
        "description": "Advanced physics study group"
      },
      "created_at": "2026-02-19T14:30:00.000000Z",
      "user": {
        "id": 1,
        "username": "admin1",
        "role": "admin"
      }
    }
  }
  ```
- **Error Response** (log not found):
  ```json
  {
    "message": "No query results for model [App\\Models\\DatabaseLog] {id}"
  }
  ```
  HTTP Status: `404`

## Frontend Integration Example

```javascript
export const getLogs = async (params = {}) => {
  try {
    const response = await axios.get(`${API_URL}/admin/logs`, {
      headers: authHeader(),
      params
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

export const getLog = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/admin/logs/${id}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```
