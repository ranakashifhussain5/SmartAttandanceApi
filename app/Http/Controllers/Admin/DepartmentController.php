<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        $departments = Department::withCount(['teachers', 'students'])
            ->with('hod.user')
            ->latest()
            ->paginate(15);

        return $this->ok(DepartmentResource::collection($departments));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return $this->ok(DepartmentResource::make($department), 'Department created', 201);
    }

    public function show(Department $department): JsonResponse
    {
        $department->load('hod.user')->loadCount(['teachers', 'students']);

        return $this->ok(DepartmentResource::make($department));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return $this->ok(DepartmentResource::make($department), 'Department updated');
    }
}
