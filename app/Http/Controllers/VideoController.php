<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
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
    try {
        $token = $request->query('token');
        $video_id = $request->query('id');
        $video = Video::find($video_id);

        if (!$video) {
            return response()->json(["message" => "الفيديو غير موجود"], 404);
        }

        $accessToken = null;
        $user = null;

        // إذا فيه توكن نحاول نجيبه
        if (!empty($token)) {
            $cleanToken = str_replace('Bearer ', '', $token);
            $accessToken = PersonalAccessToken::findToken($cleanToken);
            Log::info($token);

            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }

        // إذا الفيديو مدفوع ومافي توكن صالح
        if ($video->is_paid && !$accessToken) {
            return response()->json(["message" => "توكن غير صالح أو مفقود"], 401);
        }

        // إذا الفيديو مدفوع والمستخدم ليس مشترك ولا عنده صلاحيات خاصة
        if (
            $video->is_paid &&
            $user &&
            !$user->subscriptions()->where('course_id', $course_id)->exists() &&
            !$user->isAdmin() &&
            !$user->isTeacher() &&
            !$user->isFullAccess()
        ) {
            return response()->json(["message" => "لا يمكنك مشاهدة هذا الفيديو"], 403);
        }

        // رابط الملف في Storage Box عبر WebDAV
        $remoteUrl = "https://u484191.your-storagebox.de/uploads/videos/$folder/$course_id/$filename";
        $webdavUser = env('STORAGE_BOX_USERNAME'); 
        $webdavPass = env('STORAGE_BOX_PASSWORD'); 

        // نحصل على حجم الملف
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remoteUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$webdavUser:$webdavPass");
        curl_setopt($ch, CURLOPT_HEADER, true);
        $headers = curl_exec($ch);

        if (preg_match('/Content-Length:\s+(\d+)/i', $headers, $matches)) {
            $size = intval($matches[1]);
        } else {
            return response()->json(["message" => "تعذر قراءة حجم الملف"], 500);
        }
        curl_close($ch);

        // حساب Range
        $start = 0;
        $end = $size - 1;
        if ($request->headers->has('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches);
            $start = intval($matches[1]);
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            } else {
                $end = min($size - 1, $start + (1024 * 512)); // 512KB لكل طلب
            }
        } else {
            $end = min($size - 1, 1024 * 200); // أول Chunk = 200KB
        }

        $length = $end - $start + 1;

        // بث البيانات من WebDAV
        return response()->stream(function () use ($remoteUrl, $start, $end, $webdavUser, $webdavPass) {
            $rangeHeader = "Range: bytes=$start-$end";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remoteUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$rangeHeader]);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$webdavUser:$webdavPass");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024 * 8);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) {
                echo $data;
                flush();
                return strlen($data);
            });
            curl_exec($ch);
            curl_close($ch);
        }, 206, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes $start-$end/$size"
        ]);
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return response()->json(["message" => $e->getMessage()], 500);
    }

}}
