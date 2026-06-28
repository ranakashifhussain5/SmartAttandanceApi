<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Timetable;

class TimetableService
{
    public function ensureNoConflict(array $data, ?int $ignoreId = null): void
    {
        $conflict = Timetable::where('day', $data['day'])
            ->where('slot_id', $data['slot_id'])
            ->where(function ($query) use ($data) {
                $query->where('teacher_id', $data['teacher_id'])
                    ->orWhere('room_id', $data['room_id'])
                    ->orWhere('batch_id', $data['batch_id']);
            })
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->first();

        if ($conflict) {
            throw new BusinessException('This teacher, room, or batch is already scheduled for that day and time slot.', 409);
        }
    }
}
