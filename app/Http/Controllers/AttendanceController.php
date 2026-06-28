<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Requests\Attendance\MarkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\ClassSession;
use App\Services\AttendanceReportService;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendance,
        private AttendanceReportService $reports,
    ) {}

    public function mark(MarkAttendanceRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->fail('Only students can mark attendance.', 403);
        }

        $session = ClassSession::with('timetable.room')->findOrFail($request->validated('session_id'));

        $attendance = $this->attendance->mark($session, $student, $request->validated('wifi_mac'));

        return $this->ok(AttendanceResource::make($attendance), 'Attendance marked as present');
    }

    public function report(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            throw new BusinessException('Only teachers can view attendance reports.', 403);
        }

        $filters = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:program_courses,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $report = $this->reports->generate($teacher, $filters);

        return $this->ok(AttendanceResource::collection($report));
    }
}
