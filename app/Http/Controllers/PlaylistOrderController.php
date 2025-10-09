<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlaylistOrderController extends Controller
{
public function updateOrder(Request $request)
{
    $request->validate([
        'playlists' => 'required|array',
        'playlists.*.id' => 'required|exists:playlists,id',
        'playlists.*.order' => 'required|integer',
    ]);

    foreach ($request->playlists as $playlistData) {
        $playlist = Playlist::find($playlistData['id']);
        Log::info("Before update", ['id' => $playlistData['id'], 'order' => $playlist->order]);

        $updated = Playlist::where('id', $playlistData['id'])
            ->update(['order' => $playlistData['order']]);

        Log::info("After update", ['id' => $playlistData['id'], 'order' => $playlistData['order'], 'updated' => $updated]);
    }

    return response()->json([
        'message' => 'تم تحديث ترتيب القوائم بنجاح'
    ]);
}
}
