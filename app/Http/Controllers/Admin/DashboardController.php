<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     */
    public function getKpi()
    {
        // Mock KPI data - replace with actual data from your database
        $kpiData = [
            'total_students' => 120,
            'total_teachers' => 15,
            'total_courses' => 25,
            'active_courses' => 18,
            'recent_activities' => [
                [
                    'id' => 1,
                    'type' => 'enrollment',
                    'description' => 'New student enrolled',
                    'date' => now()->subHours(2)->toISOString()
                ],
                [
                    'id' => 2,
                    'type' => 'course',
                    'description' => 'New course created',
                    'date' => now()->subHours(5)->toISOString()
                ],
                [
                    'id' => 3,
                    'type' => 'assignment',
                    'description' => 'Assignment submitted',
                    'date' => now()->subHours(8)->toISOString()
                ]
            ]
        ];

        return response()->json($kpiData);
    }
}
