<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    public function stream(Request $request, $folder, $course_id, $filename)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(["message" => "توكن المصادقة مفقود"], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(["message" => "توكن غير صالح"], 401);
        }

        $user = $accessToken->tokenable;

        if (
            !$user->subscriptions()->where('course_id', $course_id)->exists()
            && !$user->isAdmin()
            && !$user->isTeacher()
        ) {
            return response()->json(["message" => "لا يمكنك مشاهدة هذا الفيديو"], 403);
        }

        // المسار داخل Storage Box
        $remotePath = "uploads/videos/$folder/$course_id/$filename";

        if (!Storage::disk('storagebox')->exists($remotePath)) {
            return response()->json(["message" => "هذا الفيديو غير موجود"], 404);
        }

        // قراءة الحجم
        $size = Storage::disk('storagebox')->size($remotePath);
        $start = 0;
        $end = $size - 1;

        if ($request->headers->has('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches);
            $start = intval($matches[1]);
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
        }

        $length = $end - $start + 1;

        $response = new StreamedResponse(function () use ($remotePath, $start, $length) {
            $stream = Storage::disk('storagebox')->readStream($remotePath);
            if ($stream === false) {
                return;
            }

            // تخطي البايتات حتى نصل إلى $start
            if ($start > 0) {
                fseek($stream, $start);
            }

            $buffer = 1024 * 8;
            while (!feof($stream) && $length > 0) {
                $read = ($length > $buffer) ? $buffer : $length;
                echo fread($stream, $read);
                flush();
                $length -= $read;
            }
            fclose($stream);
        });

        $status = ($start > 0 || $end < $size - 1) ? 206 : 200;

        $response->headers->set('Content-Type', 'video/mp4');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Length', $length);
        if ($status === 206) {
            $response->headers->set('Content-Range', "bytes $start-$end/$size");
        }

        $response->setStatusCode($status);

        return $response;
    }
}
