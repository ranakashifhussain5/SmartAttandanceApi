<?php

namespace App\Modules\ApplicationTracking\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ActOnApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject,forward,comment'],
            'remarks' => ['nullable', 'string'],
            'forward_to_office_id' => ['required_if:action,forward', 'integer', 'exists:offices,id'],
        ];
    }
}
