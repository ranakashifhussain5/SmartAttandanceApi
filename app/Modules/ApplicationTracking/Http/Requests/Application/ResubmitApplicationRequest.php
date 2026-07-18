<?php

namespace App\Modules\ApplicationTracking\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_data' => ['required', 'array'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file'],
        ];
    }
}
