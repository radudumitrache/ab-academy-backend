<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    use ApiResponder;

    /**
     * Display student dashboard data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Here you would typically gather dashboard data
        // such as enrolled groups, upcoming events, etc.
        
        return $this->success([
            'message' => 'Welcome to Student Dashboard',
            'role' => 'student'
        ]);
    }
}
