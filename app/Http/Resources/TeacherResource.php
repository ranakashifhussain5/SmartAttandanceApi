<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'employee_no' => $this->employee_no,
            'designation' => $this->designation,
            'phone' => $this->phone,
            'is_hod' => $this->is_hod,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'status' => $this->whenLoaded('user', fn () => $this->user->status),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'created_at' => $this->created_at,
        ];
    }
}
