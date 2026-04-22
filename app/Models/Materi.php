<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Materi extends Model
{
    protected $fillable = [
    'title',
    'category',
    'description',
    'image',
    'icon',
    'teacher_id'
];

 public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function studentLogs()
    {
        return $this->hasMany(StudentMateriLog::class);
    }
}
