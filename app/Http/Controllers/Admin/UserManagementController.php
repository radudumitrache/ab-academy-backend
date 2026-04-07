<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Invoice;
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

        DatabaseLog::logAction('create', Teacher::class, $teacher->id, "Teacher '{$teacher->username}' created");

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

        DatabaseLog::logAction('create', Student::class, $student->id, "Student '{$student->username}' created");

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

    public function getTeacher($id)
    {
        $teacher = Teacher::with(['groups.students'])->findOrFail($id);
        
        // Format groups with schedule information
        $createdGroups = $teacher->groups->map(function ($group) {
            return [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'description' => $group->description,
                'schedule_day' => $group->schedule_day,
                'schedule_time' => $group->schedule_time ?? null,
                'formatted_schedule' => $group->formatted_schedule,
                'students_count' => $group->students->count(),
                'students' => $group->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'username' => $student->username,
                        'role' => $student->role
                    ];
                })
            ];
        });
        
        // Calculate teaching stats
        $uniqueStudentIds = [];
        foreach ($teacher->groups as $group) {
            foreach ($group->students as $student) {
                $uniqueStudentIds[$student->id] = true;
            }
        }
        
        $teachingStats = [
            'total_students' => count($uniqueStudentIds),
            'total_groups' => $teacher->groups->count()
        ];
        
        return response()->json([
            'message' => 'Teacher retrieved successfully',
            'teacher' => [
                'id' => $teacher->id,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'telephone' => $teacher->telephone,
                'role' => $teacher->role,
                'languages_taught' => $teacher->languages_taught,
                'created_at' => $teacher->created_at,
                'updated_at' => $teacher->updated_at,
            ],
            'teaching_stats' => $teachingStats,
            'created_groups' => $createdGroups
        ]);
    }
    
    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $request->validate([
            'username' => 'sometimes|required|string|unique:users,username,' . $teacher->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $teacher->id,
            'telephone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'admin_notes' => 'nullable|string',
            'languages_taught' => 'nullable|array',
        ]);
        
        // Update basic fields
        if ($request->has('username')) {
            $teacher->username = $request->username;
        }
        
        if ($request->has('email')) {
            $teacher->email = $request->email;
        }
        
        if ($request->has('telephone')) {
            $teacher->telephone = $request->telephone;
        }
        
        // Update admin notes if provided
        if ($request->has('admin_notes')) {
            $teacher->admin_notes = $request->admin_notes;
        }
        
        // Update languages taught if provided
        if ($request->has('languages_taught')) {
            $teacher->languages_taught = $request->languages_taught;
        }
        
        // Update password if provided
        if ($request->has('password')) {
            $teacher->password = Hash::make($request->password);
        }
        
        $teacher->save();

        DatabaseLog::logAction('update', Teacher::class, $teacher->id, "Teacher '{$teacher->username}' updated");

        return response()->json([
            'message' => 'Teacher updated successfully',
            'teacher' => [
                'id' => $teacher->id,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'telephone' => $teacher->telephone,
                'role' => $teacher->role,
                'languages_taught' => $teacher->languages_taught,
                'admin_notes' => $teacher->admin_notes,
                'updated_at' => $teacher->updated_at,
            ],
        ]);
    }
    
    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $username = $teacher->username;
        $teacher->delete();

        DatabaseLog::logAction('delete', Teacher::class, $id, "Teacher '{$username}' deleted");

        return response()->json([
            'message' => "Teacher '{$username}' deleted successfully",
        ]);
    }

    public function getStudent($id)
    {
        $student = Student::with(['groups.teacher', 'enrolledExams', 'acquisitions.product'])->findOrFail($id);
        
        // Get student invoices
        $invoices = Invoice::where('student_id', $student->id)->orderBy('created_at', 'desc')->get();
        
        // Format enrolled groups
        $enrolledGroups = $student->groups->map(function ($group) {
            return [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'group_teacher' => $group->group_teacher,
                'description' => $group->description,
                'schedule_day' => $group->schedule_day,
                'schedule_time' => $group->schedule_time ?? null,
                'formatted_schedule' => $group->formatted_schedule,
                'teacher' => [
                    'id' => $group->teacher->id,
                    'username' => $group->teacher->username,
                    'role' => $group->teacher->role
                ]
            ];
        });
        
        // Format enrolled exams
        $enrolledExams = $student->enrolledExams->map(function ($exam) {
            return [
                'id' => $exam->id,
                'name' => $exam->name,
                'date' => $exam->date,
                'status' => $exam->status,
                'score' => $exam->pivot->score,
                'feedback' => $exam->pivot->feedback
            ];
        });
        
        // Format invoices
        $formattedInvoices = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'title' => $invoice->title,
                'series' => $invoice->series,
                'number' => $invoice->number,
                'value' => $invoice->value,
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'status' => $invoice->status,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
            ];
        });
        
        // Format product acquisitions
        $acquisitions = $student->acquisitions->map(function ($acquisition) {
            return [
                'id'                 => $acquisition->id,
                'product_id'         => $acquisition->product_id,
                'product_name'       => $acquisition->product?->name,
                'amount_paid'        => $acquisition->amount_paid,
                'currency'           => $acquisition->currency,
                'acquisition_status' => $acquisition->acquisition_status,
                'acquisition_date'   => $acquisition->acquisition_date?->format('Y-m-d'),
                'paid_at'            => $acquisition->paid_at,
            ];
        });
        
        return response()->json([
            'message' => 'Student retrieved successfully',
            'student' => [
                'id' => $student->id,
                'username' => $student->username,
                'email' => $student->email,
                'telephone' => $student->telephone,
                'address' => $student->address,
                'street' => $student->street,
                'house_number' => $student->house_number,
                'city' => $student->city,
                'county' => $student->county,
                'country' => $student->country,
                'occupation' => $student->occupation,
                'role' => $student->role,
                'admin_notes' => $student->admin_notes,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at,
            ],
            'enrolled_groups' => $enrolledGroups,
            'enrolled_exams' => $enrolledExams,
            'invoices' => $formattedInvoices,
            'acquisitions' => $acquisitions
        ]);
    }
    
    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $request->validate([
            'username' => 'sometimes|required|string|unique:users,username,' . $student->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $student->id,
            'telephone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'street' => 'nullable|string',
            'house_number' => 'nullable|string',
            'city' => 'nullable|string',
            'county' => 'nullable|string',
            'country' => 'nullable|string',
            'occupation' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'admin_notes' => 'nullable|string',
        ]);
        
        // Update basic fields
        if ($request->has('username')) {
            $student->username = $request->username;
        }
        
        if ($request->has('email')) {
            $student->email = $request->email;
        }
        
        if ($request->has('telephone')) {
            $student->telephone = $request->telephone;
        }
        
        if ($request->has('address')) {
            $student->address = $request->address;
        }
        
        if ($request->has('street')) {
            $student->street = $request->street;
        }
        
        if ($request->has('house_number')) {
            $student->house_number = $request->house_number;
        }
        
        if ($request->has('city')) {
            $student->city = $request->city;
        }
        
        if ($request->has('county')) {
            $student->county = $request->county;
        }
        
        if ($request->has('country')) {
            $student->country = $request->country;
        }
        
        if ($request->has('occupation')) {
            $student->occupation = $request->occupation;
        }
        
        if ($request->has('admin_notes')) {
            $student->admin_notes = $request->admin_notes;
        }
        
        // Update password if provided
        if ($request->has('password')) {
            $student->password = Hash::make($request->password);
        }
        
        $student->save();

        DatabaseLog::logAction('update', Student::class, $student->id, "Student '{$student->username}' updated");

        return response()->json([
            'message' => 'Student updated successfully',
            'student' => [
                'id' => $student->id,
                'username' => $student->username,
                'email' => $student->email,
                'telephone' => $student->telephone,
                'role' => $student->role,
                'admin_notes' => $student->admin_notes,
                'updated_at' => $student->updated_at,
            ],
        ]);
    }
    
    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $username = $student->username;
        $student->delete();

        DatabaseLog::logAction('delete', Student::class, $id, "Student '{$username}' deleted");

        return response()->json([
            'message' => "Student '{$username}' deleted successfully",
        ]);
    }

    public function changeStudentPassword(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $student->password = Hash::make($request->password);
        $student->save();

        DatabaseLog::logAction('update', Student::class, $student->id, "Password changed for student '{$student->username}'");

        return response()->json([
            'message' => 'Student password changed successfully',
        ]);
    }
}
