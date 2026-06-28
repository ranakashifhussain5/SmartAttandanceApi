<?php

namespace App\Http\Requests\Timetable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['sometimes', 'required', 'integer', 'exists:batches,id'],
            'program_course_id' => ['sometimes', 'required', 'integer', 'exists:program_courses,id'],
            'teacher_id' => ['sometimes', 'required', 'integer', 'exists:teachers,id'],
            'room_id' => ['sometimes', 'required', 'integer', 'exists:rooms,id'],
            'day' => ['sometimes', 'required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            'slot_id' => ['sometimes', 'required', 'integer', 'exists:time_slots,id'],
        ];
    }
}
