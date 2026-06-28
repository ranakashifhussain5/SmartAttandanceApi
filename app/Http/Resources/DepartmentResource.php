<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hod_teacher_id' => $this->hod_teacher_id,
            'hod' => TeacherResource::make($this->whenLoaded('hod')),
            'teachers_count' => $this->whenCounted('teachers'),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at,
        ];
    }
}
