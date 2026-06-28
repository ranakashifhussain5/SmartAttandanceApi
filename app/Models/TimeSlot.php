<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    protected $fillable = ['start_time', 'end_time'];

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'slot_id');
    }
}
