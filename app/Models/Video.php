<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'path',
        'course_id',
        'is_paid',
        'url',
        "duration",
        'playlist_id',

    ];
    protected $casts = [
        'is_paid' => 'boolean',
        'playlist_id' => 'integer',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }
}
