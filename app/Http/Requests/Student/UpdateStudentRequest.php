<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student')?->user_id)],
            'registration_no' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('students', 'registration_no')->ignore($this->route('student'))],
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'batch_id' => ['sometimes', 'required', 'integer', 'exists:batches,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive'],
        ];
    }
}
