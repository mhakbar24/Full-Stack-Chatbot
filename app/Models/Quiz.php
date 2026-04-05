<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'teacher_id'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
