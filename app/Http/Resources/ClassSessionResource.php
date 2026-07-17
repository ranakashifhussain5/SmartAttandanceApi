<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timetable_id' => $this->timetable_id,
            'room_id' => $this->room_id,
            'room_changed' => $this->room_id !== null,
            'session_date' => $this->session_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'timetable' => TimetableResource::make($this->whenLoaded('timetable')),
            'room' => RoomResource::make($this->whenLoaded('room')),
            'present_count' => $this->when($this->relationLoaded('attendances'), fn () => $this->attendances->where('status', 'present')->count()),
            'absent_count' => $this->when($this->relationLoaded('attendances'), fn () => $this->attendances->where('status', 'absent')->count()),
            'student_count' => $this->when($this->relationLoaded('attendances'), fn () => $this->attendances->count()),
            'created_at' => $this->created_at,
        ];
    }
}
