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
    ];
    protected $casts = [
        'is_paid' => 'boolean',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
