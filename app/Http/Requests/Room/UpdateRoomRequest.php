<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_no' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('rooms', 'room_no')->ignore($this->route('room'))],
            'beacon_major' => [
                'sometimes', 'required', 'integer', 'min:0', 'max:65535',
                Rule::unique('rooms', 'beacon_major')->ignore($this->route('room')),
            ],
            'rssi_threshold' => ['sometimes', 'nullable', 'integer', 'min:-100', 'max:0'],
        ];
    }
}
