<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentBlockController extends Controller
{
    public function __construct(private StudentBlockService $blocks) {}

    public function block(Request $request, Student $student): JsonResponse
    {
        $this->authorize('block', $student);

        $student = $this->blocks->block($student, $request->user()->teacher);

        return $this->ok(StudentResource::make($student->load('user')), 'Student blocked');
    }

    public function unblock(Request $request, Student $student): JsonResponse
    {
        $this->authorize('unblock', $student);

        $student = $this->blocks->unblock($student, $request->user()->teacher);

        return $this->ok(StudentResource::make($student->load('user')), 'Student unblocked');
    }
}
