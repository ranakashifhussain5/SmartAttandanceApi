<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramCourse extends Model
{
    protected $fillable = ['program_id', 'course_code', 'course_title', 'credit_hours'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}
