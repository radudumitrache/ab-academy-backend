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
        // Create Admin
        Admin::create([
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        // Create Teacher
        Teacher::create([
            'username' => 'teacher',
            'password' => Hash::make('password'),
        ]);

        // Create Student
        Student::create([
            'username' => 'student',
            'password' => Hash::make('password'),
        ]);
    }
}
