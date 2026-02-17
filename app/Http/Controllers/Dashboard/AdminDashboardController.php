<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use ApiResponder;

    /**
     * Display admin dashboard data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Here you would typically gather dashboard data
        // such as user counts, recent events, etc.
        
        return $this->success([
            'message' => 'Welcome to Admin Dashboard',
            'role' => 'admin'
        ]);
    }
}
