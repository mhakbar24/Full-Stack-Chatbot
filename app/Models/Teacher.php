<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
     protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
   
    public function materis()
    {
        return $this->hasMany(Materi::class);
    }
}
