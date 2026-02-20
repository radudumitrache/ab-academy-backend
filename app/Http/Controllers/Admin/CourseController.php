<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Display a listing of all courses.
     */
    public function index()
    {
        $courses = Course::with('teacher')->get();

        return response()->json([
            'message' => 'Courses retrieved successfully',
            'count' => $courses->count(),
            'courses' => $courses
        ], 200);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:50',
            'duration' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify teacher_id belongs to a teacher if provided
        if ($request->filled('teacher_id')) {
            $teacher = Teacher::find($request->teacher_id);
            if (!$teacher) {
                return response()->json([
                    'message' => 'The provided teacher_id does not belong to a teacher'
                ], 422);
            }
        }

        $course = Course::create($request->all());

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course->load('teacher')
        ], 201);
    }

    /**
     * Display the specified course.
     */
    public function show($id)
    {
        $course = Course::with('teacher')->find($id);

        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Course retrieved successfully',
            'course' => $course
        ], 200);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:50',
            'duration' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify teacher_id belongs to a teacher if provided
        if ($request->filled('teacher_id')) {
            $teacher = Teacher::find($request->teacher_id);
            if (!$teacher) {
                return response()->json([
                    'message' => 'The provided teacher_id does not belong to a teacher'
                ], 422);
            }
        }

        $course->update($request->all());

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course->fresh(['teacher'])
        ], 200);
    }

    /**
     * Remove the specified course.
     */
    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'message' => 'Course not found'
            ], 404);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ], 200);
    }
}
