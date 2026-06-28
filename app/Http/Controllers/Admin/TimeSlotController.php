<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeSlot\StoreTimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;

class TimeSlotController extends Controller
{
    public function index(): JsonResponse
    {
        $timeSlots = TimeSlot::orderBy('start_time')->paginate(15);

        return $this->ok(TimeSlotResource::collection($timeSlots));
    }

    public function store(StoreTimeSlotRequest $request): JsonResponse
    {
        $timeSlot = TimeSlot::create($request->validated());

        return $this->ok(TimeSlotResource::make($timeSlot), 'Time slot created', 201);
    }
}
