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
```
