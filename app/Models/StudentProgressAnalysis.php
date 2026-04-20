<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgressAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'student_id',
        'total_quiz',
        'average_score',
        'best_score',
        'latest_score',
        'teacher_context',
        'analysis',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
