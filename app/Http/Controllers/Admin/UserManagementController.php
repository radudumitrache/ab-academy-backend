<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function createTeacher(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $teacher = Teacher::create([
            'username' => $request->username,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Teacher created successfully',
            'teacher' => [
                'id' => $teacher->id,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'telephone' => $teacher->telephone,
                'role' => $teacher->role,
                'created_at' => $teacher->created_at,
            ],
        ], 201);
    }

    public function createStudent(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $student = Student::create([
            'username' => $request->username,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Student created successfully',
            'student' => [
                'id' => $student->id,
                'username' => $student->username,
                'email' => $student->email,
                'telephone' => $student->telephone,
                'role' => $student->role,
                'created_at' => $student->created_at,
            ],
        ], 201);
    }

    public function listTeachers()
    {
        $teachers = Teacher::all(['id', 'username', 'email', 'telephone', 'created_at']);

        return response()->json([
            'message' => 'Teachers retrieved successfully',
            'count' => $teachers->count(),
            'teachers' => $teachers,
        ]);
    }

    public function listStudents()
    {
        $students = Student::all(['id', 'username', 'email', 'telephone', 'created_at']);

        return response()->json([
            'message' => 'Students retrieved successfully',
            'count' => $students->count(),
            'students' => $students,
        ]);
    }

    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $username = $teacher->username;
        $teacher->delete();

        return response()->json([
            'message' => "Teacher '{$username}' deleted successfully",
        ]);
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $username = $student->username;
        $student->delete();

        return response()->json([
            'message' => "Student '{$username}' deleted successfully",
        ]);
    }
}
