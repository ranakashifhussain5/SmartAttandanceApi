<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'registration_no' => $this->registration_no,
            'department_id' => $this->department_id,
            'batch_id' => $this->batch_id,
            'phone' => $this->phone,
            'is_blocked' => $this->is_blocked,
            'blocked_at' => $this->blocked_at,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'status' => $this->whenLoaded('user', fn () => $this->user->status),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'attendance_percentage' => $this->when($this->relationLoaded('attendances'), fn () => $this->attendance_percentage),
            'created_at' => $this->created_at,
        ];
    }
}
