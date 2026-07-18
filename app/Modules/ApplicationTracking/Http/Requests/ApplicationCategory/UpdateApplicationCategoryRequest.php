<?php

namespace App\Modules\ApplicationTracking\Http\Requests\ApplicationCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'form_schema' => ['sometimes', 'array', 'min:1'],
            'form_schema.*.key' => ['required_with:form_schema', 'string', 'max:100'],
            'form_schema.*.label' => ['required_with:form_schema', 'string', 'max:255'],
            'form_schema.*.type' => ['required_with:form_schema', 'string', 'in:text,textarea,number,date,select,file'],
            'form_schema.*.required' => ['sometimes', 'boolean'],
            'form_schema.*.options' => ['sometimes', 'array'],
            'form_schema.*.max' => ['sometimes', 'integer'],
            'workflow_template_id' => ['sometimes', 'integer', 'exists:workflow_templates,id'],
            'applicant_roles' => ['nullable', 'array'],
            'applicant_roles.*' => ['string', 'in:admin,hod,teacher,student'],
            'allow_multiple_active' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
