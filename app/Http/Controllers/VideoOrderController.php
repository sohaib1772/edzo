<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

class VideoOrderController extends Controller
{
   public function updateOrder(Request $request)
{
    $request->validate([
        'videos' => 'required|array',
        'videos.*.id' => 'required|exists:videos,id',
        'videos.*.playlist_id' => 'nullable|exists:playlists,id', // جعلته nullable
        'videos.*.order' => 'required|integer',
    ]);

    foreach ($request->videos as $videoData) {
        Video::where('id', $videoData['id'])
            ->update([
                'playlist_id' => $videoData['playlist_id'] ?? null, // إذا لم يوجد playlist_id يصبح null
                'order' => $videoData['order'],
            ]);
    }

    return response()->json([
        'message' => 'تم تحديث ترتيب الفيديوهات والقوائم بنجاح'
    ]);
}
}