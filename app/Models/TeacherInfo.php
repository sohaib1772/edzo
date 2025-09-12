<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherInfo extends Model
{
    protected $table = 'teachers_info';
    protected $fillable = [
        'bio',
        'image',
        'telegram_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
