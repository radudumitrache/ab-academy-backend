# Frontend Integration Examples

This section provides examples of how to integrate the AB Academy API with a frontend application.

## Authentication

```javascript
import axios from 'axios';

const API_URL = 'https://api.abacademy.com/api';

// Login function
export const login = async (username, password) => {
  try {
    const response = await axios.post(`${API_URL}/admin/login`, {
      username,
      password
    });
    
    // Store token in local storage
    localStorage.setItem('admin_token', response.data.access_token);
    localStorage.setItem('admin_user', JSON.stringify(response.data.user));
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Logout function
export const logout = async () => {
  try {
    const response = await axios.post(`${API_URL}/admin/logout`, {}, {
      headers: authHeader()
    });
    
    // Clear local storage
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Auth header helper
export const authHeader = () => {
  const token = localStorage.getItem('admin_token');
  return { Authorization: `Bearer ${token}` };
};
```

## User Management

```javascript
// Get all teachers
export const getTeachers = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/teachers`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Create a new teacher
export const createTeacher = async (teacherData) => {
  try {
    const response = await axios.post(`${API_URL}/admin/teachers`, teacherData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Get all students
export const getStudents = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/students`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Get student details
export const getStudentDetails = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/admin/students/${id}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

## Groups Management

```javascript
// Get all groups
export const getGroups = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/groups`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Create a new group
export const createGroup = async (groupData) => {
  try {
    const response = await axios.post(`${API_URL}/admin/groups`, groupData, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Get schedule options
export const getScheduleOptions = async () => {
  try {
    const response = await axios.get(`${API_URL}/admin/groups/schedule/options`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Add student to group
export const addStudentToGroup = async (groupId, studentId) => {
  try {
    const response = await axios.post(`${API_URL}/admin/groups/${groupId}/students`, {
      student_id: studentId
    }, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};
```

## Dashboard

```javascript
// Get dashboard data
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

// Get KPIs
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

// Get activities
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

## Chat System

```javascript
// Get all chats
export const getChats = async () => {
  try {
    const response = await axios.get(`${API_URL}/chats`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Get chat with messages
export const getChatWithMessages = async (chatId) => {
  try {
    const response = await axios.get(`${API_URL}/chats/${chatId}`, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Send a message
export const sendMessage = async (chatId, content) => {
  try {
    const response = await axios.post(`${API_URL}/chats/${chatId}/messages`, {
      content
    }, {
      headers: authHeader()
    });
    return response.data;
  } catch (error) {
    throw error.response.data;
  }
};

// Listen for new messages using Laravel Echo
export const listenForMessages = (chatId, callback) => {
  window.Echo.private(`chat.${chatId}`)
    .listen('MessageSent', (e) => {
      callback(e.message);
    });
};
```

## React Component Example

```jsx
import React, { useState, useEffect } from 'react';
import { getStudents, createStudent } from '../api/userManagement';

const StudentManagement = () => {
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    telephone: '',
    password: ''
  });

  useEffect(() => {
    fetchStudents();
  }, []);

  const fetchStudents = async () => {
    try {
      setLoading(true);
      const response = await getStudents();
      setStudents(response.students);
      setError(null);
    } catch (err) {
      setError(err.message || 'Failed to fetch students');
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await createStudent(formData);
      setFormData({
        username: '',
        email: '',
        telephone: '',
        password: ''
      });
      fetchStudents();
    } catch (err) {
      setError(err.message || 'Failed to create student');
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>Student Management</h1>
      
      <h2>Create New Student</h2>
      <form onSubmit={handleSubmit}>
        <div>
          <label>Username:</label>
          <input
            type="text"
            name="username"
            value={formData.username}
            onChange={handleInputChange}
            required
          />
        </div>
        <div>
          <label>Email:</label>
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleInputChange}
            required
          />
        </div>
        <div>
          <label>Telephone:</label>
          <input
            type="text"
            name="telephone"
            value={formData.telephone}
            onChange={handleInputChange}
          />
        </div>
        <div>
          <label>Password:</label>
          <input
            type="password"
            name="password"
            value={formData.password}
            onChange={handleInputChange}
            required
          />
        </div>
        <button type="submit">Create Student</button>
      </form>
      
      <h2>Student List</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Telephone</th>
            <th>Created At</th>
          </tr>
        </thead>
        <tbody>
          {students.map(student => (
            <tr key={student.id}>
              <td>{student.id}</td>
              <td>{student.username}</td>
              <td>{student.email}</td>
              <td>{student.telephone || 'N/A'}</td>
              <td>{new Date(student.created_at).toLocaleDateString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default StudentManagement;
```
