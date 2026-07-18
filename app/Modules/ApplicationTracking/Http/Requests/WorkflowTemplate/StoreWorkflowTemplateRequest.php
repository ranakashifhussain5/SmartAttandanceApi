<?php

namespace App\Modules\ApplicationTracking\Http\Requests\WorkflowTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Steps are submitted as a plain ordered array — the engine wires each
     * step's "next step on approval" to the following array entry
     * automatically (linear chain, per design), so there's no need for the
     * caller to reference not-yet-created step IDs.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:255'],
            'steps.*.approver_type' => ['required', 'string', 'in:office,applicant_department_hod'],
            'steps.*.approver_office_id' => ['required_if:steps.*.approver_type,office', 'nullable', 'integer', 'exists:offices,id'],
            'steps.*.on_reject_action' => ['sometimes', 'string', 'in:terminate,return_to_applicant'],
            'steps.*.allow_forward' => ['sometimes', 'boolean'],
        ];
    }
}
