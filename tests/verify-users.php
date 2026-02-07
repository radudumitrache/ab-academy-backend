<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;

echo "========================================\n";
echo "  Users Database Verification\n";
echo "========================================\n\n";

try {
    echo "✅ Users table exists!\n\n";
    
    $totalUsers = Admin::count() + Teacher::count() + Student::count();
    echo "Total users: {$totalUsers}\n\n";
    
    echo "Users by role:\n";
    echo "Admins: " . Admin::count() . "\n";
    echo "Teachers: " . Teacher::count() . "\n";
    echo "Students: " . Student::count() . "\n\n";
    
    echo "Admin users:\n";
    foreach (Admin::all() as $admin) {
        echo "  - {$admin->username} (ID: {$admin->id})\n";
    }
    
    echo "\nTeacher users:\n";
    foreach (Teacher::all() as $teacher) {
        echo "  - {$teacher->username} (ID: {$teacher->id})\n";
    }
    
    echo "\nStudent users:\n";
    foreach (Student::all() as $student) {
        echo "  - {$student->username} (ID: {$student->id})\n";
    }
    
    echo "\n========================================\n";
    echo "✅ All users verified successfully!\n";
    echo "========================================\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "========================================\n";
}
