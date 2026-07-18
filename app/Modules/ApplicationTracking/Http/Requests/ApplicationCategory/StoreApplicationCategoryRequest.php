<?php

namespace App\Modules\ApplicationTracking\Http\Requests\ApplicationCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'form_schema' => ['required', 'array', 'min:1'],
            'form_schema.*.key' => ['required', 'string', 'max:100'],
            'form_schema.*.label' => ['required', 'string', 'max:255'],
            'form_schema.*.type' => ['required', 'string', 'in:text,textarea,number,date,select,file'],
            'form_schema.*.required' => ['sometimes', 'boolean'],
            'form_schema.*.options' => ['sometimes', 'array'],
            'form_schema.*.max' => ['sometimes', 'integer'],
            'workflow_template_id' => ['required', 'integer', 'exists:workflow_templates,id'],
            // null = open to any authenticated user.
            'applicant_roles' => ['nullable', 'array'],
            'applicant_roles.*' => ['string', 'in:admin,hod,teacher,student'],
            'allow_multiple_active' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
