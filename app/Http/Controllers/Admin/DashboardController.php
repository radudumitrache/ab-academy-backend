<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

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
        // Mock KPI data - replace with actual data from your database
        $kpiData = [
            'message' => 'KPI data retrieved successfully',
            'kpi_data' => [
                'students' => [
                    'total' => 320,
                    'active' => 247,
                    'new_this_month' => 12,
                    'growth_percentage' => 5.2
                ],
                'revenue' => [
                    'total' => '$125,000',
                    'this_month' => '$18,500',
                    'last_month' => '$17,200',
                    'growth_percentage' => 7.5
                ],
                'classes' => [
                    'total' => 42,
                    'this_week' => 18,
                    'attendance_rate' => 92
                ],
                'teachers' => [
                    'total' => 24,
                    'active' => 18
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
        
        // Mock activities data - replace with actual data from your database
        $activities = [
            [
                'id' => 1,
                'type' => 'exam',
                'description' => 'New exam scheduled - IELTS Speaking - Maria Santos',
                'user_id' => 5,
                'user_name' => 'Admin User',
                'timestamp' => Carbon::now()->subHours(2)->toIso8601String()
            ],
            [
                'id' => 2,
                'type' => 'homework',
                'description' => 'Homework uploaded - Grammar Unit 5 - Advanced Group A',
                'user_id' => 8,
                'user_name' => 'Teacher Smith',
                'timestamp' => Carbon::now()->subHours(5)->toIso8601String()
            ],
            [
                'id' => 3,
                'type' => 'enrollment',
                'description' => 'New student enrolled - John Doe - Beginner English',
                'user_id' => 5,
                'user_name' => 'Admin User',
                'timestamp' => Carbon::now()->subHours(8)->toIso8601String()
            ],
            [
                'id' => 4,
                'type' => 'payment',
                'description' => 'Payment received - $500 - Advanced English Course',
                'user_id' => 5,
                'user_name' => 'Admin User',
                'timestamp' => Carbon::now()->subHours(12)->toIso8601String()
            ],
            [
                'id' => 5,
                'type' => 'attendance',
                'description' => 'Attendance marked - Intermediate Spanish - 15 students present',
                'user_id' => 9,
                'user_name' => 'Teacher Garcia',
                'timestamp' => Carbon::now()->subHours(24)->toIso8601String()
            ],
        ];
        
        // Limit the number of activities
        $activities = array_slice($activities, 0, $limit);
        
        return response()->json([
            'message' => 'Activities retrieved successfully',
            'activities' => $activities
        ]);
    }
}
