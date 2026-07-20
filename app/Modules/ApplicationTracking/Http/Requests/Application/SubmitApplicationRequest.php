<?php

namespace App\Modules\ApplicationTracking\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sent as multipart/form-data so file fields can travel alongside the
     * rest of the form: form_data[reason]=..., attachments[medical_certificate]=<file>.
     * Per-field validation against the category's form_schema happens in
     * DynamicFormValidator, not here — this only validates the envelope.
     */
    public function rules(): array
    {
        return [
            'application_category_id' => ['required', 'integer', 'exists:application_categories,id'],
            'form_data' => ['required', 'array'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file'],
        ];
    }
}
