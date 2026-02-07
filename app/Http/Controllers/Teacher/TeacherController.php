<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

class TeacherController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'message' => 'Welcome to Teacher Dashboard',
            'role' => 'teacher'
        ]);
    }
}
