# OAuth 2.0 Authentication Guide

This Laravel application uses **Laravel Passport** for OAuth 2.0 authentication with role-based access control.

## 📋 Overview

- **Authentication Method**: OAuth 2.0 via Laravel Passport
- **User Roles**: Admin, Teacher, Student
- **Password Encryption**: Bcrypt hashing
- **Token Type**: Bearer tokens

## 🔐 User Models

The application uses separate model classes for each user type:

- `App\Models\Admin` - Admin users
- `App\Models\Teacher` - Teacher users  
- `App\Models\Student` - Student users

All inherit from the base `App\Models\User` class and use the same `users` table with a `role` field.

## 🚀 Login Endpoints

### Admin Login
```
POST /api/admin/login
```

**Request Body:**
```json
{
  "username": "admin",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "message": "Admin login successful",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "username": ["The provided credentials are incorrect."]
  }
}
```

---

### Teacher Login
```
POST /api/teacher/login
```

**Request Body:**
```json
{
  "username": "teacher",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "message": "Teacher login successful",
  "user": {
    "id": 2,
    "username": "teacher",
    "role": "teacher"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer"
}
```

---

### Student Login
```
POST /api/student/login
```

**Request Body:**
```json
{
  "username": "student",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "message": "Student login successful",
  "user": {
    "id": 3,
    "username": "student",
    "role": "student"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer"
}
```

## 🔒 Logout Endpoints

All logout endpoints require authentication (Bearer token in Authorization header).

### Admin Logout
```
POST /api/admin/logout
Authorization: Bearer {access_token}
```

### Teacher Logout
```
POST /api/teacher/logout
Authorization: Bearer {access_token}
```

### Student Logout
```
POST /api/student/logout
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "message": "Admin logout successful"
}
```

## 🛡️ Protected Routes

Protected routes require the `auth:api` middleware and a valid Bearer token.

**Example Request:**
```
GET /api/admin/dashboard
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

## 🧪 Testing with cURL

### Login Example
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

### Protected Route Example
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE"
```

### Logout Example
```bash
curl -X POST http://localhost:8000/api/admin/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE"
```

## 📝 Test Users

The database seeder creates these default users:

| Username | Password | Role    |
|----------|----------|---------|
| admin    | password | admin   |
| teacher  | password | teacher |
| student  | password | student |

**⚠️ IMPORTANT**: Change these passwords in production!

## 🔧 Verification

Run the verification script to check users:
```bash
php tests/verify-users.php
```

## 📂 Project Structure

### Models
- `app/Models/User.php` - Base user model
- `app/Models/Admin.php` - Admin model (auto-scoped to role='admin')
- `app/Models/Teacher.php` - Teacher model (auto-scoped to role='teacher')
- `app/Models/Student.php` - Student model (auto-scoped to role='student')

### Controllers
- `app/Http/Controllers/Admin/AuthController.php`
- `app/Http/Controllers/Teacher/AuthController.php`
- `app/Http/Controllers/Student/AuthController.php`

### Routes
- `routes/admin/auth.php` - Admin authentication routes
- `routes/teacher/auth.php` - Teacher authentication routes
- `routes/student/auth.php` - Student authentication routes

## 🔑 OAuth 2.0 Clients

Passport creates two OAuth clients during installation:

1. **Personal Access Client** (ID: 1)
   - For generating personal access tokens
   
2. **Password Grant Client** (ID: 2)
   - For password-based authentication
   - Client Secret: `0DyZRlubObTHDNRbGDu2NMIC3ZXWFgbzt8g5zDZo`

## 🚨 Security Notes

1. **Passwords**: All passwords are hashed using bcrypt
2. **Tokens**: Access tokens are stored in the `oauth_access_tokens` table
3. **Token Revocation**: Logout revokes the current access token
4. **Role Isolation**: Each role has separate login endpoints and can only access their own resources

## 📚 Next Steps

1. Add password reset functionality
2. Implement refresh token support
3. Add email verification
4. Create role-specific middleware
5. Add rate limiting to login endpoints
6. Implement two-factor authentication (2FA)
