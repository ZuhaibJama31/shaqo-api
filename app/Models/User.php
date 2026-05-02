<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'firebase_uid'
    ];

    protected static function booted()
{
    static::created(function ($user) {
        
        if ($user->role === 'client') {
            $user->client()->create([]);
        }

        if ($user->role === 'worker') {
            $user->worker()->create([
                // request() grabs it from Postman. 
                // Defaulting to 1 prevents Filament from crashing.
                'category_id'  => request('category_id') ?? 1, 
                //'hourly_rate'  => 0,
                'is_available' => true,
                'rating'       => 0,
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
