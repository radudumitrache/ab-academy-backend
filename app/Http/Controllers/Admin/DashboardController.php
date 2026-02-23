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
use App\Models\Invoice;

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
        
        // Revenue statistics from paid invoices
        $totalRevenue = 0;
        $revenueThisMonth = 0;
        $revenueLastMonth = 0;
        $revenueGrowthPercentage = 0;
        
        if (Schema::hasTable('invoices')) {
            // Calculate total revenue from paid invoices
            $paidInvoices = Invoice::where('status', 'paid')->get();
            
            // Calculate total revenue
            foreach ($paidInvoices as $invoice) {
                // Convert all values to EUR for consistency
                if ($invoice->currency === 'RON') {
                    // Assuming 1 EUR = 4.9 RON (you may want to use a real exchange rate service)
                    $totalRevenue += $invoice->value / 4.9;
                } else {
                    $totalRevenue += $invoice->value;
                }
            }
            
            // Calculate revenue this month
            $paidInvoicesThisMonth = Invoice::where('status', 'paid')
                ->where('updated_at', '>=', $startOfMonth)
                ->get();
                
            foreach ($paidInvoicesThisMonth as $invoice) {
                if ($invoice->currency === 'RON') {
                    $revenueThisMonth += $invoice->value / 4.9;
                } else {
                    $revenueThisMonth += $invoice->value;
                }
            }
            
            // Calculate revenue last month
            $paidInvoicesLastMonth = Invoice::where('status', 'paid')
                ->whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])
                ->get();
                
            foreach ($paidInvoicesLastMonth as $invoice) {
                if ($invoice->currency === 'RON') {
                    $revenueLastMonth += $invoice->value / 4.9;
                } else {
                    $revenueLastMonth += $invoice->value;
                }
            }
            
            // Calculate revenue growth percentage
            if ($revenueLastMonth > 0) {
                $revenueGrowthPercentage = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
            }
        }
        
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
                    'total' => '€' . number_format($totalRevenue, 2),
                    'this_month' => '€' . number_format($revenueThisMonth, 2),
                    'last_month' => '€' . number_format($revenueLastMonth, 2),
                    'growth_percentage' => round($revenueGrowthPercentage, 2)
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
    
    /**
     * Search for users, events, and groups based on name similarity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validate search query
        $request->validate([
            'query' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
            'type' => 'nullable|string|in:all,users,events,groups',
        ]);
        
        $query = $request->input('query');
        $limit = $request->input('limit', 10);
        $type = $request->input('type', 'all');

        // Initialize results arrays
        $results = [];
        $users = [];
        $events = [];
        $groups = [];

        $queryLower = strtolower($query);

        // Allow up to 50% character difference for fuzzy matching
        $maxDistance = max(2, (int) floor(strlen($query) * 0.5));

        // Returns a relevance score (0–100) or -1 if no match.
        // Priority: exact (100) > contains (85) > fuzzy (10–75)
        $matchScore = function (string $field) use ($queryLower, $maxDistance): int {
            $fieldLower = strtolower($field);

            if ($fieldLower === $queryLower) {
                return 100;
            }

            if (str_contains($fieldLower, $queryLower)) {
                return 85;
            }

            $distance = levenshtein($queryLower, $fieldLower);
            if ($distance <= $maxDistance) {
                return max(10, 75 - ($distance * 15));
            }

            return -1;
        };

        // Search users if type is 'all' or 'users'
        if ($type === 'all' || $type === 'users') {
            $allUsers = User::all(['id', 'username', 'email', 'role']);

            foreach ($allUsers as $user) {
                $score = $matchScore($user->username);

                if ($score >= 0) {
                    $users[] = [
                        'id' => $user->id,
                        'name' => $user->username,
                        'email' => $user->email,
                        'type' => 'user',
                        'role' => $user->role,
                        'relevance' => $score,
                    ];
                }
            }

            usort($users, fn($a, $b) => $b['relevance'] - $a['relevance']);
            $users = array_slice($users, 0, $limit);
        }

        // Search events if type is 'all' or 'events'
        if ($type === 'all' || $type === 'events') {
            $allEvents = DB::table('events')->select('id', 'title', 'type', 'event_date')->get();

            foreach ($allEvents as $event) {
                $score = $matchScore($event->title);

                if ($score >= 0) {
                    $events[] = [
                        'id' => $event->id,
                        'name' => $event->title,
                        'type' => 'event',
                        'event_type' => $event->type,
                        'event_date' => $event->event_date,
                        'relevance' => $score,
                    ];
                }
            }

            usort($events, fn($a, $b) => $b['relevance'] - $a['relevance']);
            $events = array_slice($events, 0, $limit);
        }

        // Search groups if type is 'all' or 'groups'
        if ($type === 'all' || $type === 'groups') {
            if (Schema::hasTable('groups')) {
                $allGroups = DB::table('groups')->select('group_id', 'group_name', 'description')->get();

                foreach ($allGroups as $group) {
                    $score = $matchScore($group->group_name);

                    if ($score >= 0) {
                        $groups[] = [
                            'id' => $group->group_id,
                            'name' => $group->group_name,
                            'description' => $group->description,
                            'type' => 'group',
                            'relevance' => $score,
                        ];
                    }
                }

                usort($groups, fn($a, $b) => $b['relevance'] - $a['relevance']);
                $groups = array_slice($groups, 0, $limit);
            }
        }

        // Combine results based on type
        if ($type === 'all') {
            $results = array_merge($users, $events, $groups);
            usort($results, fn($a, $b) => $b['relevance'] - $a['relevance']);
            $results = array_slice($results, 0, $limit);
        } elseif ($type === 'users') {
            $results = $users;
        } elseif ($type === 'events') {
            $results = $events;
        } elseif ($type === 'groups') {
            $results = $groups;
        }
        
        return response()->json([
            'message' => 'Search results retrieved successfully',
            'query' => $query,
            'count' => count($results),
            'results' => $results
        ]);
    }
}
