<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'department_id' => $this->department_id,
            'department' => $this->when($this->relationLoaded('department') && $this->department, fn () => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ]),
            'users' => $this->when($this->relationLoaded('users'), fn () => $this->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
