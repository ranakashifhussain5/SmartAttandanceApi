<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Http\JsonResponse;

class TeacherController extends Controller
{
    public function __construct(private TeacherService $teachers) {}

    public function index(): JsonResponse
    {
        $teachers = Teacher::with(['user', 'department'])->latest()->paginate(15);

        return $this->ok(TeacherResource::collection($teachers));
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $teacher = $this->teachers->create($request->validated(), verified: true);

        return $this->ok(TeacherResource::make($teacher->load(['user', 'department'])), 'Teacher created', 201);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        return $this->ok(TeacherResource::make($teacher->load(['user', 'department'])));
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher = $this->teachers->update($teacher, $request->validated());

        return $this->ok(TeacherResource::make($teacher->load(['user', 'department'])), 'Teacher updated');
    }
}
