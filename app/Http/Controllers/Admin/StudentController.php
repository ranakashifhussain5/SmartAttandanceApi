<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(private StudentService $students) {}

    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['user', 'department', 'batch']);

        if ($request->user()->isHod()) {
            $query->where('department_id', $request->user()->teacher?->department_id);
        }

        $students = $query->latest()->paginate(15);

        return $this->ok(StudentResource::collection($students));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->students->create($request->validated());

        return $this->ok(StudentResource::make($student->load(['user', 'department', 'batch'])), 'Student created', 201);
    }

    public function show(Student $student): JsonResponse
    {
        return $this->ok(StudentResource::make($student->load(['user', 'department', 'batch'])));
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student = $this->students->update($student, $request->validated());

        return $this->ok(StudentResource::make($student->load(['user', 'department', 'batch'])), 'Student updated');
    }
}
