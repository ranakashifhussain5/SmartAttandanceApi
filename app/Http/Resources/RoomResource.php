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
            'beacon_major' => $this->beacon_major,
            'rssi_threshold' => $this->rssi_threshold,
            'created_at' => $this->created_at,
        ];
    }
}
