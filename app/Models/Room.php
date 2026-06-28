<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['room_no', 'wifi_name', 'wifi_mac'];

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}
