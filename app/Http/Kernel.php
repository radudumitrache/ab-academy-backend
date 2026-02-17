<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'role.admin' => \App\Http\Middleware\Role\AdminMiddleware::class,
        'role.teacher' => \App\Http\Middleware\Role\TeacherMiddleware::class,
        'role.student' => \App\Http\Middleware\Role\StudentMiddleware::class,
    ];
}
