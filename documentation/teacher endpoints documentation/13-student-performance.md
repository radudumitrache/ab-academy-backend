# Student Performance

Endpoints to retrieve homework and test submission grades and observations for students in the teacher's groups.

> **Scope**: Only students belonging to groups where the authenticated teacher is the **main teacher or an assistant teacher** are accessible. Requesting a student outside those groups returns `403`.

---

## Get All Students' Performance

- **URL**: `/api/teacher/students/performance`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Student performance retrieved successfully",
    "count": 1,
    "data": [
      {
        "student": {
          "id": 5,
          "username": "john_doe",
          "email": "john@example.com",
          "admin_notes": "Strong in writing, needs help with grammar."
        },
        "performance": {
          "summary": {
            "homework_submitted": 4,
            "homework_graded": 3,
            "test_submitted": 2,
            "test_graded": 2,
            "average_grade": 78.5
          },
          "homework_submissions": [
            {
              "id": 12,
              "homework_id": 3,
              "title": "Unit 4 Grammar Exercise",
              "status": "graded",
              "grade": 85,
              "observation": "Good effort, minor article errors.",
              "submitted_at": "2026-03-10T14:00:00+00:00"
            }
          ],
          "test_submissions": [
            {
              "id": 7,
              "test_id": 2,
              "title": "Reading Comprehension Test",
              "status": "graded",
              "grade": 72,
              "observation": "Struggled with inference questions.",
              "submitted_at": "2026-03-15T09:30:00+00:00"
            }
          ]
        }
      }
    ]
  }
  ```

---

## Get Single Student Performance

- **URL**: `/api/teacher/students/{id}/performance`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: Student user ID
- **Success Response**: Same shape as the single-item object in the list above.
- **Error Response** (student not in teacher's groups):
  ```json
  { "message": "Student not found in your groups" }
  ```
  HTTP Status: `403`
- **Error Response** (student does not exist):
  ```json
  { "message": "No query results for model [App\\Models\\Student] 99" }
  ```
  HTTP Status: `404`

---

## Field Reference

| Field | Description |
|-------|-------------|
| `admin_notes` | Free-text notes written by the admin for this student |
| `observation` | Teacher's written feedback on the specific submission |
| `grade` | Numeric grade assigned by the teacher (`null` if not yet graded) |
| `status` | Submission status (`submitted`, `graded`, etc.) |
| `average_grade` | Mean of all graded homework and test grades; `null` if nothing graded yet |

---

## Frontend Integration Example

```javascript
const API_URL = 'https://backend.andreeaberkhout.com/api';

const authHeader = () => ({
  Authorization: `Bearer ${localStorage.getItem('teacher_token')}`
});

// All students in teacher's groups
export const getAllStudentPerformance = async () => {
  const response = await axios.get(`${API_URL}/teacher/students/performance`, {
    headers: authHeader()
  });
  return response.data;
};

// Single student
export const getStudentPerformance = async (studentId) => {
  const response = await axios.get(`${API_URL}/teacher/students/${studentId}/performance`, {
    headers: authHeader()
  });
  return response.data;
};
```
