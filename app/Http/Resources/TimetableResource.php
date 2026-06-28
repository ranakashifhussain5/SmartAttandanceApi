<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'program_course_id' => $this->program_course_id,
            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,
            'day' => $this->day,
            'slot_id' => $this->slot_id,
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'course' => ProgramCourseResource::make($this->whenLoaded('course')),
            'teacher' => TeacherResource::make($this->whenLoaded('teacher')),
            'room' => RoomResource::make($this->whenLoaded('room')),
            'time_slot' => TimeSlotResource::make($this->whenLoaded('timeSlot')),
            'created_at' => $this->created_at,
        ];
    }
}
