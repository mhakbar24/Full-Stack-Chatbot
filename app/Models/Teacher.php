<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
     protected $fillable = ['name', 'email', 'password', 'profile_photo_path'];
    protected $hidden = ['password'];
    protected $appends = ['profile_photo_url'];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }
   
    public function materis()
    {
        return $this->hasMany(Materi::class);
    }

    public function progressAnalyses()
    {
        return $this->hasMany(StudentProgressAnalysis::class);
    }
}
