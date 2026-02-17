<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminUsername = env('SUPER_ADMIN_USERNAME', 'admin');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', 'password');

        // Create or update Super Admin from environment credentials
        Admin::updateOrCreate(
            ['username' => $superAdminUsername],
            ['password' => Hash::make($superAdminPassword)]
        );

        // Create Teacher
        Teacher::firstOrCreate([
            'username' => 'teacher',
        ], [
            'password' => Hash::make('password'),
        ]);

        // Create Student
        Student::firstOrCreate([
            'username' => 'student',
        ], [
            'password' => Hash::make('password'),
        ]);
    }
}
