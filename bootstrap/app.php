<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\MissingRateLimiterException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'verified' => \App\Http\Middleware\VerifiedMiddleware::class,
            'retry_db' => \App\Http\Middleware\RetryDatabaseConnection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        //throtle exception
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json(['message' => "لقد تجاوزت الحد المسموح به يرجى المحاولة في وقت لاحق"], 429);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
        return response()->json(['message' => 'غير مصرح. الرجاء تسجيل الدخول أولاً.'], 403);
    });
    })->create();
