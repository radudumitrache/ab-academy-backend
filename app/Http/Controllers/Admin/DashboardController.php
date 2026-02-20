<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
    /**
     * Get dashboard main data
     */
    public function index()
    {
        return response()->json([
            'message' => 'Admin Dashboard',
            'user' => Auth::user(),
            'role' => 'admin'
        ]);
    }

    /**
     * Get dashboard KPI data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKpi()
    {
        // Get real data from database
        
        // Student statistics
        $totalStudents = Student::count();
        
        // Count students created this month
        $startOfMonth = Carbon::now()->startOfMonth();
        $newStudentsThisMonth = Student::where('created_at', '>=', $startOfMonth)->count();
        
        // Calculate growth percentage
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $studentsLastMonth = Student::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        
        $growthPercentage = 0;
        if ($studentsLastMonth > 0) {
            $growthPercentage = (($newStudentsThisMonth - $studentsLastMonth) / $studentsLastMonth) * 100;
        }
        
        // Teacher statistics
        $totalTeachers = Teacher::count();
        
        // Group/Class statistics
        $totalGroups = 0;
        if (Schema::hasTable('groups')) {
            $totalGroups = DB::table('groups')->count();
        }
        
        // This week's classes
        $startOfWeek = Carbon::now()->startOfWeek();
        $classesThisWeek = 0;
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'event_date')) {
            $classesThisWeek = DB::table('events')
                ->where('type', 'class')
                ->where('event_date', '>=', $startOfWeek)
                ->count();
        }
        
        $kpiData = [
            'message' => 'KPI data retrieved successfully',
            'kpi_data' => [
                'students' => [
                    'total' => $totalStudents,
                    'new_this_month' => $newStudentsThisMonth,
                    'growth_percentage' => round($growthPercentage, 2)
                ],
                'revenue' => [
                    'total' => '$0',
                    'this_month' => '$0',
                    'last_month' => '$0',
                    'growth_percentage' => 0
                ],
                'classes' => [
                    'total' => $totalGroups,
                    'this_week' => $classesThisWeek,
                    'attendance_rate' => 0
                ],
                'teachers' => [
                    'total' => $totalTeachers
                ]
            ]
        ];

        return response()->json($kpiData);
    }
    
    /**
     * Get recent activities for the dashboard
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivities(Request $request)
    {
        // Get the limit parameter or default to 10
        $limit = $request->input('limit', 10);
        
        // Validate limit is a positive integer
        $limit = max(1, min(50, (int) $limit));
        
        // Get real activities from database logs
        $logs = DB::table('database_logs')
            ->select('id', 'action as type', 'description', 'user_id', 'user_role', 'created_at as timestamp', 'model')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        // Format logs as activities
        $activities = [];
        foreach ($logs as $log) {
            // Try to get the user name if user_id exists
            $userName = 'Unknown User';
            if ($log->user_id) {
                $user = User::find($log->user_id);
                if ($user) {
                    $userName = $user->username;
                }
            }
            
            $activities[] = [
                'id' => $log->id,
                'type' => $log->type,
                'description' => $log->description,
                'user_id' => $log->user_id,
                'user_name' => $userName,
                'model' => $log->model,
                'timestamp' => Carbon::parse($log->timestamp)->toIso8601String()
            ];
        }
        
        return response()->json([
            'message' => 'Activities retrieved successfully',
            'activities' => $activities
        ]);
    }
}
