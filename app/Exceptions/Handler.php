<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * سجل الاستثناءات.
     */
    public function register(): void
    {
        //
    }

    /**
     * التعامل مع استثناء عدم المصادقة.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'message' => 'غير مصرح. الرجاء تسجيل الدخول أولاً.'
        ], 401);
    }
}