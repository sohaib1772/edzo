<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;


class UploadCunkedController extends Controller
{

    public function upload(Request $request)
    {
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded()) {
            $save = $receiver->receive();

            Log::info("Received chunk", [
                'chunk_number' => $request->resumableChunkNumber,
                'total_chunks' => $request->resumableTotalChunks,
                'filename' => $request->resumableFilename,
                'resumableTotalSize' => $request->resumableTotalSize,
            ]);

            Log::info("Is finished: " . json_encode($save->isFinished()));

            if ($save->isFinished()) {
                $file = $save->getFile();
                $filename = $file->getClientOriginalName();

                $file->storeAs('public/uploads/videos', $filename);
                Log::info("✔ تم تجميع الملف: " . $filename);

                return response()->json(['success' => true, 'filename' => $filename]);
            }

            return response()->json(['success' => true, 'chunk_received' => true]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
