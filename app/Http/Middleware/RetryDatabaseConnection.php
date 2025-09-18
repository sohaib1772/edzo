<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class RetryDatabaseConnection
{
    public function handle($request, Closure $next)
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // تحقق من اتصال قاعدة البيانات
                DB::connection()->getPdo();

                // إذا نجح الاتصال تابع الطلب
                return $next($request);

            } catch (PDOException $e) {
                $attempt++;
                Log::warning("DB connection failed, attempt $attempt: " . $e->getMessage());

                // إذا تجاوزنا المحاولات، أعطِ الخطأ
                if ($attempt >= $maxRetries) {
                    throw $e;
                }

                // انتظر ثانية قبل المحاولة التالية
                sleep(1);

                // أعد الاتصال
                DB::reconnect();
            }
        }
    }
}