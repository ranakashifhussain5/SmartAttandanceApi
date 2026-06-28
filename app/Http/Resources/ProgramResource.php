<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'name' => $this->name,
            'code' => $this->code,
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'created_at' => $this->created_at,
        ];
    }
}
