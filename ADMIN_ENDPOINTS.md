# Admin User Management & Database Logging

This document describes the admin endpoints for managing teachers and students, plus the database logging system.

## 🎯 Overview

Admins can:
- Create teacher and student accounts
- List all teachers and students
- Delete teacher and student accounts
- View database activity logs

All database changes are automatically logged with:
- **Who** made the change (user ID and role)
- **What** was changed (action, model, description)
- **When** it happened (timestamp)

## 📝 Database Logging

### How It Works

The `DatabaseLog` model automatically tracks all database changes using Laravel Observers:
- **Created**: When a new user is created
- **Updated**: When a user is modified
- **Deleted**: When a user is deleted

### Log Structure

```json
{
  "id": 1,
  "action": "created",
  "model": "App\\Models\\Teacher",
  "model_id": 5,
  "user_id": 1,
  "user_role": "admin",
  "description": "User 'john_teacher' with role 'teacher' was created",
  "changes": {
    "username": "john_teacher",
    "role": "teacher"
  },
  "created_at": "2026-02-07 22:30:00"
}
```

## 🔐 Admin Endpoints

All endpoints require authentication with an admin Bearer token.

### Create Teacher

```
POST /api/admin/teachers
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "john_teacher",
  "password": "securepassword123"
}
```

**Success Response (201):**
```json
{
  "message": "Teacher created successfully",
  "teacher": {
    "id": 5,
    "username": "john_teacher",
    "role": "teacher",
    "created_at": "2026-02-07T22:30:00.000000Z"
  }
}
```

**Validation Errors (422):**
```json
{
  "message": "The username has already been taken.",
  "errors": {
    "username": ["The username has already been taken."]
  }
}
```

---

### Create Student

```
POST /api/admin/students
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "jane_student",
  "password": "securepassword123"
}
```

**Success Response (201):**
```json
{
  "message": "Student created successfully",
  "student": {
    "id": 6,
    "username": "jane_student",
    "role": "student",
    "created_at": "2026-02-07T22:31:00.000000Z"
  }
}
```

---

### List All Teachers

```
GET /api/admin/teachers
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "message": "Teachers retrieved successfully",
  "count": 2,
  "teachers": [
    {
      "id": 2,
      "username": "teacher",
      "created_at": "2026-02-07T22:25:53.000000Z"
    },
    {
      "id": 5,
      "username": "john_teacher",
      "created_at": "2026-02-07T22:30:00.000000Z"
    }
  ]
}
```

---

### List All Students

```
GET /api/admin/students
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "message": "Students retrieved successfully",
  "count": 2,
  "students": [
    {
      "id": 3,
      "username": "student",
      "created_at": "2026-02-07T22:25:54.000000Z"
    },
    {
      "id": 6,
      "username": "jane_student",
      "created_at": "2026-02-07T22:31:00.000000Z"
    }
  ]
}
```

---

### Delete Teacher

```
DELETE /api/admin/teachers/{id}
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "message": "Teacher 'john_teacher' deleted successfully"
}
```

**Not Found (404):**
```json
{
  "message": "No query results for model [App\\Models\\Teacher] 999"
}
```

---

### Delete Student

```
DELETE /api/admin/students/{id}
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "message": "Student 'jane_student' deleted successfully"
}
```

---

### View Database Logs

```
GET /api/admin/logs
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `action` - Filter by action (created, updated, deleted)
- `model` - Filter by model name
- `user_id` - Filter by user who made the change
- `per_page` - Results per page (default: 50)
- `page` - Page number

**Success Response (200):**
```json
{
  "message": "Database logs retrieved successfully",
  "logs": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "action": "created",
        "model": "App\\Models\\Teacher",
        "model_id": 5,
        "user_id": 1,
        "user_role": "admin",
        "description": "User 'john_teacher' with role 'teacher' was created",
        "changes": {
          "username": "john_teacher",
          "role": "teacher"
        },
        "created_at": "2026-02-07T22:30:00.000000Z"
      },
      {
        "id": 4,
        "action": "created",
        "model": "App\\Models\\Student",
        "model_id": 3,
        "user_id": null,
        "user_role": null,
        "description": "User 'student' with role 'student' was created",
        "changes": {
          "username": "student",
          "role": "student"
        },
        "created_at": "2026-02-07T22:25:54.000000Z"
      }
    ],
    "per_page": 50,
    "total": 5
  }
}
```

---

### View Single Log Entry

```
GET /api/admin/logs/{id}
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "message": "Database log retrieved successfully",
  "log": {
    "id": 5,
    "action": "created",
    "model": "App\\Models\\Teacher",
    "model_id": 5,
    "user_id": 1,
    "user_role": "admin",
    "description": "User 'john_teacher' with role 'teacher' was created",
    "changes": {
      "username": "john_teacher",
      "role": "teacher"
    },
    "created_at": "2026-02-07T22:30:00.000000Z",
    "user": {
      "id": 1,
      "username": "admin",
      "role": "admin"
    }
  }
}
```

## 🧪 Testing with cURL

### 1. Login as Admin
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

Save the `access_token` from the response.

### 2. Create a Teacher
```bash
curl -X POST http://localhost:8000/api/admin/teachers \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"username":"john_teacher","password":"password123"}'
```

### 3. Create a Student
```bash
curl -X POST http://localhost:8000/api/admin/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"username":"jane_student","password":"password123"}'
```

### 4. List All Teachers
```bash
curl -X GET http://localhost:8000/api/admin/teachers \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 5. View Database Logs
```bash
curl -X GET "http://localhost:8000/api/admin/logs?per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 6. Delete a Teacher
```bash
curl -X DELETE http://localhost:8000/api/admin/teachers/5 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📊 Database Log Filters

### Filter by Action
```bash
curl -X GET "http://localhost:8000/api/admin/logs?action=created" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Filter by Model
```bash
curl -X GET "http://localhost:8000/api/admin/logs?model=Teacher" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Filter by User
```bash
curl -X GET "http://localhost:8000/api/admin/logs?user_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🔒 Security Notes

1. **Authentication Required**: All endpoints require a valid admin Bearer token
2. **Role Validation**: Only users with role='admin' can access these endpoints
3. **Password Hashing**: All passwords are automatically hashed with bcrypt
4. **Username Uniqueness**: Usernames must be unique across all users
5. **Audit Trail**: All actions are logged in `database_logs` table

## 📁 Files Created

- `app/Models/DatabaseLog.php` - Database log model
- `app/Observers/UserObserver.php` - Tracks user changes
- `app/Http/Controllers/Admin/UserManagementController.php` - User CRUD
- `app/Http/Controllers/Admin/DatabaseLogController.php` - Log viewing
- `routes/admin/users.php` - User management routes
- `routes/admin/logs.php` - Log viewing routes
- `database/migrations/2024_01_02_000000_create_database_logs_table.php`

## 🎯 Next Steps

1. Add role-based middleware to enforce admin-only access
2. Add pagination to teacher/student lists
3. Add search/filter functionality for users
4. Add bulk user creation
5. Add user update endpoints
6. Export logs to CSV/PDF
