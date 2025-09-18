<?php

namespace App\Http\Controllers;

use App\Services\PlaylistServices;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    //

    protected $playlistServices;

    public function __construct(PlaylistServices $playlistServices)
    {
        $this->playlistServices = $playlistServices;
    }

    public function create(Request $request)
    {
        return $this->playlistServices->createPlaylist($request);
    }

    public function getAllByCourse(Request $request,$courseId)
    {
        return $this->playlistServices->getAllPlaylistsByCourse($request,$courseId);
    }
    public function update(Request $request,$playList_id)
    {
        return $this->playlistServices->updatePlaylist($request,$playList_id);
    }
    public function delete(Request $request,$playList_id)
    {
        return $this->playlistServices->deletePlaylist($request,$playList_id);
    }
}
