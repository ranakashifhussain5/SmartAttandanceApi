<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_no' => ['required', 'string', 'max:50', 'unique:rooms,room_no'],
            'wifi_name' => ['required', 'string', 'max:100'],
            'wifi_mac' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', 'unique:rooms,wifi_mac'],
        ];
    }
}
