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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // 3. Add the authorization logic
    public function canAccessPanel(Panel $panel): bool
    {
        // Example: Only allow users with a 'admin' role
        return $this->role === 'admin';
        
        // Or for testing/local development, just return true:
        // return true;
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

