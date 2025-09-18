<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'teacher_id',
    ];

    // العلاقة مع الفيديوهات (playlist تحتوي على عدة فيديوهات)
     public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    // العلاقة مع المعلم
    public function teacher()
    {
        return $this->belongsTo(User::class, );
    }
}
