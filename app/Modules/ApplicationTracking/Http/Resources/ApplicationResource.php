<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_category_id' => $this->application_category_id,
            'category' => $this->when($this->relationLoaded('category') && $this->category, fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'applicant_user_id' => $this->applicant_user_id,
            'applicant' => $this->when($this->relationLoaded('applicant') && $this->applicant, fn () => [
                'id' => $this->applicant->id,
                'name' => $this->applicant->name,
                'role' => $this->applicant->role,
            ]),
            'form_data' => $this->form_data,
            'current_step_id' => $this->current_step_id,
            'current_step' => $this->when($this->relationLoaded('currentStep'), fn () => $this->currentStep ? [
                'id' => $this->currentStep->id,
                'name' => $this->currentStep->name,
            ] : null),
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'resolved_at' => $this->resolved_at,
            'timeline' => ApplicationActionResource::collection($this->whenLoaded('actions')),
            'attachments' => ApplicationAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
        ];
    }
}
