<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::latest()->paginate(15);

        return $this->ok(RoomResource::collection($rooms));
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['wifi_mac'] = strtoupper($data['wifi_mac']);

        $room = Room::create($data);

        return $this->ok(RoomResource::make($room), 'Room created', 201);
    }

    public function show(Room $room): JsonResponse
    {
        return $this->ok(RoomResource::make($room));
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['wifi_mac'])) {
            $data['wifi_mac'] = strtoupper($data['wifi_mac']);
        }

        $room->update($data);

        return $this->ok(RoomResource::make($room), 'Room updated');
    }
}
