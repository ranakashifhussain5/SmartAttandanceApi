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
            // Must match BEACON_ROOM_NUMBER flashed into this room's ESP32-S3 beacon_config.h.
            'beacon_major' => ['required', 'integer', 'min:0', 'max:65535', 'unique:rooms,beacon_major'],
            'rssi_threshold' => ['nullable', 'integer', 'min:-100', 'max:0'],
        ];
    }
}
