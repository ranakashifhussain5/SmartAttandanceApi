<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'batch_name' => $this->batch_name,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'semester' => $this->semester,
            'shift' => $this->shift,
            'program' => ProgramResource::make($this->whenLoaded('program')),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at,
        ];
    }
}
