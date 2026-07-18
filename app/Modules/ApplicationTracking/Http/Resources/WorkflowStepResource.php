<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step_order' => $this->step_order,
            'name' => $this->name,
            'approver_type' => $this->approver_type,
            'approver_office_id' => $this->approver_office_id,
            'office' => OfficeResource::make($this->whenLoaded('office')),
            'on_approve_next_step_id' => $this->on_approve_next_step_id,
            'on_reject_action' => $this->on_reject_action,
            'allow_forward' => $this->allow_forward,
        ];
    }
}
