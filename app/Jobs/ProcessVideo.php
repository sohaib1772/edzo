<?php

namespace App\Jobs;

use FFMpeg\FFMpeg;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, Queueable;

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
        $ffmpeg = FFMpeg::create(['ffmpeg.binaries' => base_path('ffmpeg/bin/ffmpeg.exe'), 'ffprobe.binaries' => base_path('ffmpeg/bin/ffprobe.exe'), 'timeout' => 3600, 'ffmpeg.threads' => 12]);

        $video = $ffmpeg->open($this->localPath);

        $hlsFolder = storage_path("app/{$this->baseFolder}/{$this->filename}-hls");
        if (!file_exists($hlsFolder)) mkdir($hlsFolder, 0755, true);

        $format = new \FFMpeg\Format\Video\X264('aac');
        $format->setAdditionalParameters([
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
            if ($f->isFile()) {
                $stream = fopen($f->getRealPath(), 'r');
                $relativePath = "{$this->baseFolder}/{$this->filename}-hls/" . $f->getFilename();
                Storage::disk('storagebox')->put($relativePath, $stream);
                fclose($stream);
            }
        }

        // حذف الملفات المحلية
        unlink($this->localPath);
        // يمكنك إضافة حذف مجلد HLS
    }
}
