<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            // UUID read off the beacon's iBeacon advertisement during the BLE scan.
            'detected_uuid' => ['required', 'string', 'regex:/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/'],
            // Room number read off the beacon's iBeacon Major field during the BLE scan.
            'detected_major' => ['required', 'integer', 'min:0', 'max:65535'],
            'rssi' => ['required', 'integer', 'min:-100', 'max:0'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
