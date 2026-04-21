<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'worker_id',
        'description',
        'address',
        'city',
        'scheduled_at',
        'status',
        'agreed_price',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
