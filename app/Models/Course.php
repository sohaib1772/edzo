<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    //

    protected $fillable = [
        'title',
        'description',
        'image',
        'price',
        'user_id',
    ];
    protected $casts = [
        'price' => 'integer',
    ];
    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function codes()
    {
        return $this->hasMany(Code::class);
    }
    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'subscriptions', 'course_id', 'user_id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
