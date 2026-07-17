<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the "class_sessions" table (an instance of a Timetable entry on a
 * specific date). Named ClassSession rather than Session to avoid clashing
 * with Laravel's own session-storage concepts.
 */
class ClassSession extends Model
{
    protected $table = 'class_sessions';

    protected $fillable = ['timetable_id', 'room_id', 'session_date', 'start_time', 'end_time', 'status'];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * Per-day room override. Null means this session uses the timetable's
     * regular room — see effectiveRoom().
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function effectiveRoom(): Room
    {
        return $this->room ?? $this->timetable->room;
    }
}
