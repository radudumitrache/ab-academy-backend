<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'message' => 'Welcome to Teacher Dashboard',
            'role' => 'teacher'
        ]);
    }

    /**
     * List all teachers (id + username) except the authenticated one.
     * Useful for looking up colleagues to add as assistant teachers.
     */
    public function index()
    {
        $teachers = Teacher::where('id', '!=', Auth::id())
            ->select('id', 'username')
            ->orderBy('username')
            ->get();

        return response()->json([
            'message'  => 'Teachers retrieved successfully',
            'teachers' => $teachers,
        ]);
    }
}
