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
            'wifi_mac_detected' => $this->wifi_mac_detected,
            'status' => $this->status,
            'marked_at' => $this->marked_at,
            'student' => StudentResource::make($this->whenLoaded('student')),
            'session' => ClassSessionResource::make($this->whenLoaded('session')),
            'created_at' => $this->created_at,
        ];
    }
}
