<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'student_id' => $this->student_id,
            'detected_uuid' => $this->detected_uuid,
            'detected_major' => $this->detected_major,
            'rssi' => $this->rssi,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'marked_at' => $this->marked_at,
            'student' => StudentResource::make($this->whenLoaded('student')),
            'session' => ClassSessionResource::make($this->whenLoaded('session')),
            'created_at' => $this->created_at,
        ];
    }
}
