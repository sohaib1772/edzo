<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    //
    protected $fillable = [
        'code',
        'course_id',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
