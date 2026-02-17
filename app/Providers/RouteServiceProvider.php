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

            // Admin routes
            Route::middleware('api')
                ->prefix('api/admin')
                ->group(function () {
                    require base_path('routes/admin/auth.php');
                    require base_path('routes/admin/dashboard.php');
                    require base_path('routes/admin/users.php');
                    require base_path('routes/admin/logs.php');
                    require base_path('routes/admin/groups.php');
                    require base_path('routes/admin/events.php');
                });

            // Teacher routes
            Route::middleware('api')
                ->prefix('api/teacher')
                ->group(function () {
                    require base_path('routes/teacher/auth.php');
                    require base_path('routes/teacher/dashboard.php');
                    require base_path('routes/teacher/ai_assistant.php');
                });

            // Student routes
            Route::middleware('api')
                ->prefix('api/student')
                ->group(function () {
                    require base_path('routes/student/auth.php');
                    require base_path('routes/student/dashboard.php');
                });

            // Live session routes
            Route::middleware('api')
                ->prefix('api/live-sessions')
                ->group(function () {
                    require base_path('routes/live_sessions/event_routes.php');
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
