<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'bio',
        'hourly_rate',
        'experience_years',
        'is_available',
        'rating',
        'total_jobs',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'rating'       => 'float',
        'hourly_rate'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
