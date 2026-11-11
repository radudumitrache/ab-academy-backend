<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
     * Whether admin login and logout are written to the database_logs table.
     * Paused by default; set LOG_AUTH_EVENTS_TO_DATABASE=true to resume.
     */
    'database_auth_events' => (bool) env('LOG_AUTH_EVENTS_TO_DATABASE', false),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],
];
