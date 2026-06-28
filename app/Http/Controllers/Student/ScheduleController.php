<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\TimetableResource;
use App\Models\ClassSession;
use App\Models\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function todayClasses(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        $todayName = now()->format('l');

        $timetables = Timetable::where('batch_id', $student->batch_id)
            ->where('day', $todayName)
            ->with(['course', 'teacher.user', 'room', 'timeSlot'])
            ->get();

        $sessions = ClassSession::whereDate('session_date', today())
            ->whereIn('timetable_id', $timetables->pluck('id'))
            ->with(['attendances' => fn ($query) => $query->where('student_id', $student->id)])
            ->get()
            ->keyBy('timetable_id');

        $classes = $timetables->map(function (Timetable $timetable) use ($sessions, $student) {
            $session = $sessions->get($timetable->id);
            $attendance = $session?->attendances->first();

            return [
                'timetable' => TimetableResource::make($timetable),
                'session_status' => $session?->status ?? 'none',
                'session_id' => $session?->id,
                'attendance_status' => $attendance?->status,
            ];
        });

        return $this->ok($classes);
    }

    public function schedule(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $timetables = Timetable::where('batch_id', $student->batch_id)
            ->with(['course', 'teacher.user', 'room', 'timeSlot'])
            ->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
            ->get()
            ->groupBy('day');

        return $this->ok(
            $timetables->map(fn ($items) => TimetableResource::collection($items))
        );
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        $attendances = $student->attendances()
            ->with(['session.timetable.course', 'session.timetable.room'])
            ->latest('id')
            ->paginate(15);

        return $this->ok(AttendanceResource::collection($attendances));
    }
}
