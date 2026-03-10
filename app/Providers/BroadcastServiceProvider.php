<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Broadcasting\BroadcastServiceProvider as BaseBroadcastServiceProvider;

class BroadcastServiceProvider extends BaseBroadcastServiceProvider
{
    /**
     * Bootstrap any application services.
     * Overrides the default to use auth:api (Passport) instead of web (session).
     */
    public function boot(): void
    {
        // Register /broadcasting/auth protected by Passport token (not session)
        Broadcast::routes(['middleware' => ['auth:api']]);

        require base_path('routes/channels.php');
    }
}
