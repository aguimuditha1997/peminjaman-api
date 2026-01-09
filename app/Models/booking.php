<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class booking extends Model
{
    protected $fillable = [
        'name',
        'organization',
        'email',
        'status_dpt',
        'status_sdm',
        'type_week',
        'no_whatsapp',
        'room_id',
        'start_time',
        'end_time',
        'note',
        'purpose',
    ];

    public function room()

    {
        return $this->belongsTo(Room::class);
    }

}
