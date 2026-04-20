<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class Student extends Authenticatable
{
     use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'profile_photo_path',
    ];

    protected $hidden = [
        'password',  
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }

    public function progressAnalyses()
    {
        return $this->hasMany(StudentProgressAnalysis::class);
    }

    public function materiLogs()
    {
        return $this->hasMany(StudentMateriLog::class);
    }
}
