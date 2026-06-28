<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'registration_no' => ['required', 'string', 'max:50', 'unique:students,registration_no'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
