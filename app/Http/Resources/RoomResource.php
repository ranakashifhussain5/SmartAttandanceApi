<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_no' => $this->room_no,
            'wifi_name' => $this->wifi_name,
            'wifi_mac' => $this->wifi_mac,
            'created_at' => $this->created_at,
        ];
    }
}
