<?php

namespace App\Jobs;

use FFMpeg\FFMpeg;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $timeout = 3600; // 1 ساعة
    public $tries = 3;      // عدد المحاولات


    protected $localPath;
    protected $baseFolder;
    protected $filename;

    public function __construct($localPath, $baseFolder, $filename)
    {
        $this->localPath = $localPath;
        $this->baseFolder = $baseFolder;
        $this->filename = $filename;
    }

    public function handle()
    {
        Log::info("Starting HLS conversion: {$this->filename}");
        $ffmpeg = FFMpeg::create(['ffmpeg.binaries' => base_path('ffmpeg/bin/ffmpeg.exe'), 'ffprobe.binaries' => base_path('ffmpeg/bin/ffprobe.exe'), 'timeout' => 3600, 'ffmpeg.threads' => 12]);


        $video = $ffmpeg->open($this->localPath);

        $hlsFolder = storage_path("app/{$this->baseFolder}/{$this->filename}-hls");
        if (!file_exists($hlsFolder)) mkdir($hlsFolder, 0755, true);

        $format = new \FFMpeg\Format\Video\X264('aac');

        // إضافة copy codecs بدلاً من إعادة الترميز
        $format->setAdditionalParameters([
            '-c:v',
            'copy',
            '-c:a',
            'copy',
            '-hls_time',
            '6',
            '-hls_list_size',
            '0',
            '-hls_segment_filename',
            "$hlsFolder/segment_%03d.ts"
        ]);

        $video->save($format, "$hlsFolder/playlist.m3u8");

        // رفع إلى Storage Box
        $allHlsFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($hlsFolder)
        );

        foreach ($allHlsFiles as $f) {
            Log::info("start HLS uploading to storage box: {$this->filename}");
            if ($f->isFile()) {
                $stream = fopen($f->getRealPath(), 'r');
                $relativePath = "{$this->baseFolder}/{$this->filename}-hls/" . $f->getFilename();
                Storage::disk('storagebox')->put($relativePath, $stream);
                fclose($stream);
            }
            Log::info("end HLS uploading to storage box: {$this->filename}");
        }
        Log::info("Finished HLS conversion: {$this->filename}");
        // حذف الملفات المحلية
        unlink($this->localPath);
        // يمكنك إضافة حذف مجلد HLS
    }
}
