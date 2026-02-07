# Database Models Documentation

## 📊 Users Table

The application uses a single `users` table with a `role` field to differentiate between user types.

### Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `username` | string | Unique username |
| `password` | string | Hashed password |
| `role` | enum | User role: `admin`, `teacher`, or `student` |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

## 🔧 Running Migrations

### Create the database tables:

```bash
php artisan migrate
```

### Seed with example users:

```bash
php artisan db:seed
```

This creates:
- **Admin**: username: `admin`, password: `password`
- **Teacher**: username: `teacher`, password: `password`
- **Student**: username: `student`, password: `password`

### Fresh migration with seed:

```bash
php artisan migrate:fresh --seed
```

## 📝 User Model

### Basic Usage

```php
use App\Models\User;

// Create a new user
$user = User::create([
    'username' => 'john_doe',
    'password' => Hash::make('secret'),
    'role' => 'student',
]);

// Find user by username
$user = User::where('username', 'admin')->first();

// Check user role
if ($user->isAdmin()) {
    // Admin logic
}

if ($user->isTeacher()) {
    // Teacher logic
}

if ($user->isStudent()) {
    // Student logic
}
```

### Query Scopes

```php
// Get all admins
$admins = User::admins()->get();

// Get all teachers
$teachers = User::teachers()->get();

// Get all students
$students = User::students()->get();
```

## 🔐 Password Hashing

Always hash passwords before storing:

```php
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'username' => 'new_user',
    'password' => Hash::make('plain_password'),
    'role' => 'student',
]);

// Verify password
if (Hash::check('plain_password', $user->password)) {
    // Password is correct
}
```

## 🎯 Example Controller Usage

### Admin Controller

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function teachers()
    {
        $teachers = User::teachers()->get();
        return response()->json($teachers);
    }

    public function students()
    {
        $students = User::students()->get();
        return response()->json($students);
    }
}
```

### Teacher Controller

```php
namespace App\Http\Controllers\Teacher;

use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        // Get all students
        $students = User::students()->get();
        return response()->json($students);
    }
}
```

## 🚀 Next Steps

1. **Add Authentication**: Implement login/logout with Laravel Sanctum
2. **Add Middleware**: Protect routes based on user roles
3. **Extend Models**: Add more fields (email, name, profile info)
4. **Add Relationships**: Link users to courses, assignments, etc.

## 📋 Common Queries

```php
// Count users by role
$adminCount = User::admins()->count();
$teacherCount = User::teachers()->count();
$studentCount = User::students()->count();

// Get recent users
$recentUsers = User::latest()->take(10)->get();

// Find specific user
$user = User::find(1);
$user = User::where('username', 'admin')->first();

// Update user
$user->update([
    'password' => Hash::make('new_password')
]);

// Delete user
$user->delete();
```

## ⚠️ Important Notes

1. **Never store plain passwords** - Always use `Hash::make()`
2. **Username is unique** - Duplicate usernames will fail
3. **Role validation** - Only `admin`, `teacher`, `student` are allowed
4. **Password is hidden** - Not included in JSON responses by default
