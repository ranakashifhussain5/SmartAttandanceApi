<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'form_schema' => $this->form_schema,
            'workflow_template_id' => $this->workflow_template_id,
            'workflow_template' => WorkflowTemplateResource::make($this->whenLoaded('workflowTemplate')),
            'applicant_roles' => $this->applicant_roles,
            'allow_multiple_active' => $this->allow_multiple_active,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
