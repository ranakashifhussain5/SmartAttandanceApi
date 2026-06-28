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
            'wifi_name' => ['sometimes', 'required', 'string', 'max:100'],
            'wifi_mac' => [
                'sometimes', 'required', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
                Rule::unique('rooms', 'wifi_mac')->ignore($this->route('room')),
            ],
        ];
    }
}
