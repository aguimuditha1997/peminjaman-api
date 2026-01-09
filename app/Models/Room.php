<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'nameroom',
        'capacity',
        'detail',
        'images',
    ];
    protected $casts = [
    'images' => 'array',
    ];

    public function booking()
    {
        return $this->hasMany(Booking::class);
    }
}
