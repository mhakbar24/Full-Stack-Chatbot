<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMateriLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'materi_id',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function materi()
    {
        return $this->belongsTo(Materi::class);
    }
}
