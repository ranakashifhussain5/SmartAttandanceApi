<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'course_code' => $this->course_code,
            'course_title' => $this->course_title,
            'credit_hours' => $this->credit_hours,
            'program' => ProgramResource::make($this->whenLoaded('program')),
            'created_at' => $this->created_at,
        ];
    }
}
