<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin auth routes (public)
            Route::middleware('api')
                ->prefix('api/admin')
                ->group(function () {
                    require base_path('routes/admin/auth.php');
                });
                
            // Protected admin routes
            Route::middleware(['api', 'auth:api'])
                ->prefix('api/admin')
                ->group(function () {
                    require base_path('routes/admin/dashboard.php');
                    require base_path('routes/admin/users.php');
                    require base_path('routes/admin/logs.php');
                    require base_path('routes/admin/groups.php');
                    require base_path('routes/admin/events.php');
                    require base_path('routes/admin/courses.php');
                    require base_path('routes/admin/archive.php');
                    require base_path('routes/admin/exams.php');
                    require base_path('routes/admin/student_details.php');
                    require base_path('routes/admin/invoices.php');
                    require base_path('routes/admin/notifications.php');
                    require base_path('routes/admin/materials.php');
                    require base_path('routes/admin/homework.php');
                    require base_path('routes/admin/tests.php');
                    require base_path('routes/admin/chat.php');
                    require base_path('routes/admin/meeting_accounts.php');
                    require base_path('routes/admin/profile.php');
                    require base_path('routes/admin/products.php');
                    require base_path('routes/admin/anaf.php');
                });

            // Teacher routes
            Route::middleware('api')
                ->prefix('api/teacher')
                ->group(function () {
                    require base_path('routes/teacher/auth.php');
                    require base_path('routes/teacher/dashboard.php');
                    require base_path('routes/teacher/ai_assistant.php');
                    require base_path('routes/teacher/groups.php');
                    require base_path('routes/teacher/exams.php');
                    require base_path('routes/teacher/events.php');
                    require base_path('routes/teacher/notifications.php');
                    require base_path('routes/teacher/homework.php');
                    require base_path('routes/teacher/tests.php');
                    require base_path('routes/teacher/materials.php');
                    require base_path('routes/teacher/profile.php');
                });

            // Student routes
            Route::middleware('api')
                ->prefix('api/student')
                ->group(function () {
                    require base_path('routes/student/auth.php');
                    require base_path('routes/student/dashboard.php');
                    require base_path('routes/student/groups.php');
                    require base_path('routes/student/materials.php');
                    require base_path('routes/student/homework.php');
                    require base_path('routes/student/tests.php');
                    require base_path('routes/student/events.php');
                    require base_path('routes/student/exams.php');
                    require base_path('routes/student/profile.php');
                    require base_path('routes/student/schedule.php');
                    require base_path('routes/student/chat.php');
                    require base_path('routes/student/notifications.php');
                    require base_path('routes/student/invoices.php');
                    require base_path('routes/student/payment_profiles.php');
                    require base_path('routes/student/products.php');
                    require base_path('routes/student/anaf.php');
                });

            // EuPlatesc webhook routes (no auth — called directly by EuPlatesc servers)
            Route::middleware('api')
                ->prefix('api/euplatesc')
                ->group(function () {
                    require base_path('routes/euplatesc.php');
                });

            // Live session routes
            Route::middleware('api')
                ->prefix('api/live-sessions')
                ->group(function () {
                    require base_path('routes/live_sessions/event_routes.php');
                });

            // Chat routes
            Route::middleware('api')
                ->prefix('api')
                ->group(function () {
                    require base_path('routes/api/chat.php');
                });
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
