<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Support\Facades\Log;

class UploadVideoServices
{

    public function upload_video(Request $request, $folder)
    {
        $course_id = $request->course_id;
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded()) {
            $save = $receiver->receive();

            if ($save->isFinished()) {
                $file = $save->getFile();
                $filename = $file->getClientOriginalName();

                // بناء مسار المجلد الموحد
                $baseFolder = "uploads/videos/$folder/$course_id";

                // انشاء المجلد اذا لم يكن موجود
                $directory = storage_path("app/$baseFolder");
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // حفظ الملف مؤقتًا في storage/app/...
                $path = $file->storeAs($baseFolder, $filename);

                Log::info("✔ تم تجميع الملف: " . $filename);
                Log::info("✔ مسار التخزين المحلي: " . $path);

                // المسار الكامل للملف المحلي
                $localFullPath = storage_path("app/private/$path");

                if (!file_exists($localFullPath)) {
                    Log::error("✘ الملف غير موجود: " . $localFullPath);
                    return false;
                }

                // فتح الملف للقراءة كستريم
                $stream = fopen($localFullPath, 'r');

                // المسار داخل Storage Box (نفس هيكل المجلد)
                $remotePath = $baseFolder . '/' . $filename;

                // رفع الملف إلى Storage Box
                $uploaded = Storage::disk('storagebox')->put($remotePath, $stream);

                fclose($stream);

                if ($uploaded) {
                    Log::info("✔ تم نقل الملف إلى Storage Box: " . $remotePath);

                    // حذف الملف المحلي المؤقت بعد الرفع
                    unlink($localFullPath);

                    // حذف ملف التجميع من مجلد chunks
                    $chunksDir = storage_path('app/private/chunks');
                    $pattern = $chunksDir . '/' . $filename . '-*.part';
                    foreach (glob($pattern) as $chunkFile) {
                        @unlink($chunkFile);
                        Log::info("✔ تم حذف ملف التجميع/البارت: " . $chunkFile);
                    }

                    return $path;
                    // تعيد المسار المحلي للملف المؤقت (أو يمكنك تعديلها)
                } else {
                    Log::error("✘ فشل نقل الملف إلى Storage Box");
                    return false;
                }
            }

            return false; // لم تكتمل عملية الرفع
        }

        return false; // لم يتم رفع أي ملف
    }

    public function delete_video($path)
    {
        Log::info(Storage::path($path)); // يظهر المسار الكامل للملف

        if (Storage::exists($path)) {
            Storage::delete($path);
            Log::info('Video deleted!');
        } else {
            Log::info('Video not found!');
        }
    }
}
