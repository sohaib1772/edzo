<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistServices
{
    public function getAllPlaylistsByCourse(Request $request, $courseId)
{
    $course = Course::with(['playlists' => function ($query) {
        $query->orderBy('order')->with(['videos' => function($q) {
            $q->orderBy('order');
        }]);
    }])->find($courseId);

    if (!$course) {
        return response()->json([
            "message" => "هذه الدورة غير موجودة"
        ], 404);
    }

    return response()->json($course->playlists->map(function ($playlist) {
        return [
            "id"     => $playlist->id,
            "title"  => $playlist->title,
            "videos" => $playlist->videos->makeHidden(['url']),
        ];
    }));
}

    public function createPlaylist(Request $request)
    {
        $user = Auth::user();
        $user = User::find($user->id);

        $data = $request->validate([
            "title" => "required|string",
            "course_id" => "required|exists:courses,id",
        ],[
            "title.required" => "اسم القائمة مطلوب",
            "title.string" => "اسم القائمة يجب ان يكون نصية",
            "course_id.required" => "الدورة مطلوبة",
            "course_id.exists" => "الدورة غير موجودة",
            ]);

        $playlist = $user->courses()->find($data["course_id"])->playlists()->create([
            "title" => $data["title"],
            "course_id" => $data["course_id"],
            "teacher_id" => $user->id
        ]);
        return response()->json(
             $playlist
        );
    }

    public function updatePlaylist(Request $request,$playlist_id)
    {
        $user = Auth::user();
        $user = User::find($user->id);

        $data = $request->validate([
            "title" => "required|string",
        ],[
            "title.required" => "اسم القائمة مطلوب",
            "title.string" => "اسم القائمة يجب ان يكون نصية",
            ]);

        $playlist = Playlist::find($playlist_id)->update([
            "title" => $data["title"],
        ]);
        return response()->json([
            "data" => $playlist
        ]);
    }
    public function deletePlaylist(Request $request,$playlist_id)
    {
       
        $playlist = Playlist::find($playlist_id);
        if (!$playlist) {
            return response()->json([
                "message" => "هذا القائمة غير موجودة"
            ], 404);
        }
        $playlist->videos()->delete();
        $playlist->delete();
       
        return response()->json([
        ], 200);
    }
}
