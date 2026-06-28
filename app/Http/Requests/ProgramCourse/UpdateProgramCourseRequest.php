<?php

namespace App\Http\Requests\ProgramCourse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
            'course_code' => ['sometimes', 'required', 'string', 'max:20'],
            'course_title' => ['sometimes', 'required', 'string', 'max:255'],
            'credit_hours' => ['sometimes', 'required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
