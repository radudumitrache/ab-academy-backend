# Application Structure

This Laravel API is organized by user roles: **Teacher**, **Student**, and **Admin**.

## 📁 Folder Structure

```
app/
└── Http/
    └── Controllers/
        ├── Teacher/          # Teacher-specific controllers
        ├── Student/          # Student-specific controllers
        └── Admin/            # Admin-specific controllers

routes/
├── api.php               # General API routes
├── teacher.php           # Teacher-specific routes
├── student.php           # Student-specific routes
└── admin.php             # Admin-specific routes
```

## 🔗 Route Prefixes

All routes are automatically prefixed:

- **General API**: `/api/*`
- **Teacher API**: `/api/teacher/*`
- **Student API**: `/api/student/*`
- **Admin API**: `/api/admin/*`

## 📝 Example Endpoints

### General
- `GET /api/hello` - Hello World endpoint

### Teacher
- `GET /api/teacher/dashboard` - Teacher dashboard

### Student
- `GET /api/student/dashboard` - Student dashboard

### Admin
- `GET /api/admin/dashboard` - Admin dashboard

## 🎯 How to Add New Endpoints

### 1. Create a Controller

**Teacher Example:**
```bash
# Create in: app/Http/Controllers/Teacher/CourseController.php
```

```php
<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

class CourseController extends Controller
{
    public function index()
    {
        return response()->json([
            'courses' => []
        ]);
    }

    public function store()
    {
        // Create course logic
    }
}
```

### 2. Add Route

**In `routes/teacher.php`:**
```php
use App\Http\Controllers\Teacher\CourseController;

Route::get('/courses', [CourseController::class, 'index']);
Route::post('/courses', [CourseController::class, 'store']);
```

### 3. Access Endpoint

```
GET http://localhost:8000/api/teacher/courses
POST http://localhost:8000/api/teacher/courses
```

## 🔐 Adding Authentication

Later, you can protect routes with middleware:

```php
// In routes/teacher.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
});
```

## 📊 Typical Endpoint Structure

### Teacher Endpoints
- Courses (CRUD)
- Assignments (CRUD)
- Students (view, grade)
- Schedule

### Student Endpoints
- Enrolled courses (view)
- Assignments (view, submit)
- Grades (view)
- Schedule

### Admin Endpoints
- Users (CRUD)
- Courses (CRUD)
- Reports
- System settings

## 🧪 Testing Endpoints

```bash
# Start server
php artisan serve

# Test endpoints
curl http://localhost:8000/api/hello
curl http://localhost:8000/api/teacher/dashboard
curl http://localhost:8000/api/student/dashboard
curl http://localhost:8000/api/admin/dashboard
```

## 🚀 Next Steps

1. Define your database schema (migrations)
2. Create models for your entities
3. Implement authentication (Laravel Sanctum)
4. Add authorization (policies/gates)
5. Build out CRUD operations for each role
