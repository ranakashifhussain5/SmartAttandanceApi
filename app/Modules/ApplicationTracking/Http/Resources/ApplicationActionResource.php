<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_step_id' => $this->workflow_step_id,
            'step_name' => $this->when($this->relationLoaded('step') && $this->step, fn () => $this->step->name),
            'actor_user_id' => $this->actor_user_id,
            'actor_name' => $this->when($this->relationLoaded('actor') && $this->actor, fn () => $this->actor->name),
            'action' => $this->action,
            'remarks' => $this->remarks,
            'forwarded_to_office_id' => $this->forwarded_to_office_id,
            'form_data_snapshot' => $this->form_data_snapshot,
            'attachments' => ApplicationAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
        ];
    }
}
