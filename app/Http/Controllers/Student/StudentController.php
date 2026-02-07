<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'message' => 'Welcome to Student Dashboard',
            'role' => 'student'
        ]);
    }
}
