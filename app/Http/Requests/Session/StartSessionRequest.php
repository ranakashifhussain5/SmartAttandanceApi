<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    /**
     * Authorization is handled in the controller via TimetablePolicy::start,
     * same as before this request class existed.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }
}
