<?php

namespace App\Services;

use App\Jobs\ProcessVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;

class UploadVideoServices
{
    public function upload_video(Request $request, $folder)
{
    try {
        $course_id = $request->course_id;
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) return false;

        $save = $receiver->receive();
        if (!$save->isFinished()) return false;

        $file = $save->getFile();
        $filenameWithoutExt = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $baseFolder = "uploads/videos/$folder/$course_id";
        $directory = storage_path("app/$baseFolder");
        if (!file_exists($directory)) mkdir($directory, 0755, true);

        // حفظ الملف مؤقتاً
        $localPath = $file->storeAs($baseFolder, $file->getClientOriginalName(), 'private');
        $localFullPath = str_replace('\\', '/', storage_path("app/private/$localPath"));

        if (!file_exists($localFullPath)) {
            Log::error("✘ File does not exist at: $localFullPath");
            return false;
        }

        // ===== إعداد مجلد HLS =====
        $hlsFolder = $directory . "/$filenameWithoutExt-hls";
        if (!file_exists($hlsFolder)) mkdir($hlsFolder, 0755, true);

        // ===== إعداد FFmpeg و FFProbe =====
         // ===== إرسال مهمة Queue لمعالجة الفيديو لاحقاً =====
        ProcessVideo::dispatch($localFullPath, $baseFolder, $filenameWithoutExt);

        

        // ===== إعادة مسار Master Playlist =====
        return "$baseFolder/$filenameWithoutExt-hls/playlist.m3u8";

    } catch (\Exception $e) {
        Log::error("FFMpeg error: " . $e->getMessage());
        return false;
    }
}

    // حذف مجلد كامل
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function delete_video($courseId, $path)
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);

        $baseFolder = "uploads/videos/courses_videos/$courseId/$filename-hls";
        if (Storage::disk('storagebox')->exists($baseFolder)) {
            $files = Storage::disk('storagebox')->allFiles($baseFolder);
            foreach ($files as $f) Storage::disk('storagebox')->delete($f);
            Log::info("تم حذف الفيديو بالكامل: $filename");
            return true;
        }
        return false;
    }
}
