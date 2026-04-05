<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    protected $fillable = ['result_id', 'question_id', 'answer', 'is_correct'];

    public function result()
    {
        return $this->belongsTo(QuizResult::class, 'result_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
