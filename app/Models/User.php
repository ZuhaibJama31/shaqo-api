<?php

namespace App\Models;

// 1. Import the Filament contract and Panel class
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// 2. Add "implements FilamentUser"
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'city',
    ];
protected static function booted()
{
    static::created(function ($user) {

        if ($user->role === 'client') {
            $user->client()->create();
        }

        if ($user->role === 'worker') {
            $user->worker()->create([
            ]);
        }

    });
}
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

   
    public function canAccessPanel(Panel $panel): bool
    {
        
        return $this->role === 'admin';
        
    }

    public function worker()
    {
        return $this->hasOne(Worker::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }
}

