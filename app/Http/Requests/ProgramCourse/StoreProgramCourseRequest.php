<?php

namespace App\Http\Requests\ProgramCourse;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'course_code' => ['required', 'string', 'max:20'],
            'course_title' => ['required', 'string', 'max:255'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
