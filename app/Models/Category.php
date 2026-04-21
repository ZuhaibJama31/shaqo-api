<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_so', 'icon'];

    public function workers()
    {
        return $this->hasMany(Worker::class);
    }
}
