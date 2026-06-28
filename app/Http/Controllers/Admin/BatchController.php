<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Batch\StoreBatchRequest;
use App\Http\Requests\Batch\UpdateBatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{
    public function index(): JsonResponse
    {
        $batches = Batch::with('program')->withCount('students')->latest()->paginate(15);

        return $this->ok(BatchResource::collection($batches));
    }

    public function store(StoreBatchRequest $request): JsonResponse
    {
        $batch = Batch::create($request->validated());

        return $this->ok(BatchResource::make($batch), 'Batch created', 201);
    }

    public function show(Batch $batch): JsonResponse
    {
        $batch->load('program')->loadCount('students');

        return $this->ok(BatchResource::make($batch));
    }

    public function update(UpdateBatchRequest $request, Batch $batch): JsonResponse
    {
        $batch->update($request->validated());

        return $this->ok(BatchResource::make($batch), 'Batch updated');
    }
}
