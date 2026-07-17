<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionService
{
    public function __construct(
        private NotificationService $notifications,
        private AuditLogService $auditLog,
    ) {}

    public function start(Timetable $timetable, User $teacherUser, ?int $roomId = null): ClassSession
    {
        $this->assertWithinPeriodWindow($timetable);

        $today = now()->toDateString();

        $alreadyActive = ClassSession::where('timetable_id', $timetable->id)
            ->where('session_date', $today)
            ->where('status', 'active')
            ->exists();

        if ($alreadyActive) {
            throw new BusinessException('A session for this class is already active today.', 409);
        }

        $roomChanged = $roomId !== null && $roomId !== $timetable->room_id;
        $room = $roomChanged ? Room::findOrFail($roomId) : $timetable->room;

        $this->assertRoomHasBeacon($room);
        $this->assertRoomIsFree($room, $today);

        return DB::transaction(function () use ($timetable, $teacherUser, $today, $roomId, $roomChanged, $room) {
            $session = ClassSession::create([
                'timetable_id' => $timetable->id,
                'room_id' => $roomChanged ? $roomId : null,
                'session_date' => $today,
                'start_time' => $timetable->timeSlot->start_time,
                'end_time' => $timetable->timeSlot->end_time,
                'status' => 'active',
            ]);

            $students = Student::where('batch_id', $timetable->batch_id)->get();

            if ($students->isNotEmpty()) {
                Attendance::insert($students->map(fn (Student $student) => [
                    'session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }

            $message = $roomChanged
                ? "Room changed for today's class. Attendance has started for {$timetable->course->course_title} — open the app in Room {$room->room_no} to mark your attendance."
                : "Attendance has started for {$timetable->course->course_title}. Open the app in Room {$room->room_no} to mark your attendance.";

            $this->notifications->sendMany(
                $students->pluck('user_id'),
                'Attendance Started',
                $message,
                'attendance_started',
                ['session_id' => $session->id, 'room_id' => $room->id, 'room_no' => $room->room_no, 'room_changed' => $roomChanged],
            );

            $auditMessage = "Started session for timetable #{$timetable->id}"
                .($roomChanged ? " (room changed to {$room->room_no} for today)" : '');

            $this->auditLog->log($teacherUser->id, 'session_started', $auditMessage, $session);

            return $session->load(['timetable.course', 'timetable.room', 'timetable.batch', 'timetable.teacher', 'room']);
        });
    }

    private function assertRoomHasBeacon(Room $room): void
    {
        if (! $room->beacon_uuid || $room->beacon_major === null) {
            throw new BusinessException("Room {$room->room_no} has no attendance beacon configured and can't be used to start a session.");
        }
    }

    private function assertRoomIsFree(Room $room, string $today): void
    {
        $occupied = ClassSession::where('session_date', $today)
            ->where('status', 'active')
            ->where(function ($query) use ($room) {
                $query->where('room_id', $room->id)
                    ->orWhere(function ($query) use ($room) {
                        $query->whereNull('room_id')
                            ->whereHas('timetable', fn ($t) => $t->where('room_id', $room->id));
                    });
            })
            ->exists();

        if ($occupied) {
            throw new BusinessException("Room {$room->room_no} is already in use by another active session right now.", 409);
        }
    }

    private function assertWithinPeriodWindow(Timetable $timetable): void
    {
        $slot = $timetable->timeSlot;
        $now = now();

        $start = Carbon::createFromTimeString($slot->start_time);
        $end = Carbon::createFromTimeString($slot->end_time);

        if ($now->lt($start)) {
            throw new BusinessException(
                "This period hasn't started yet. Attendance can be started at {$start->format('h:i A')}."
            );
        }

        if ($now->gt($end)) {
            throw new BusinessException(
                "This period has already ended at {$end->format('h:i A')}. Attendance can no longer be started."
            );
        }
    }

    public function end(ClassSession $session, User $teacherUser): ClassSession
    {
        if ($session->status !== 'active') {
            throw new BusinessException('This session is not active.', 409);
        }

        return DB::transaction(function () use ($session, $teacherUser) {
            $session->update(['status' => 'ended']);

            $absentees = $session->attendances()->where('status', 'absent')->with('student')->get();

            foreach ($absentees as $attendance) {
                $this->notifications->send(
                    $attendance->student->user_id,
                    'Attendance Marked',
                    'You were marked absent for this session.',
                    'attendance_marked',
                    ['session_id' => $session->id, 'status' => 'absent'],
                );
            }

            $presentCount = $session->attendances()->where('status', 'present')->count();
            $absentCount = $absentees->count();

            $this->notifications->send(
                $teacherUser->id,
                'Session Ended',
                "Session ended: {$presentCount} present, {$absentCount} absent.",
                'session_ended',
                ['session_id' => $session->id, 'present' => $presentCount, 'absent' => $absentCount],
            );

            $this->auditLog->log($teacherUser->id, 'session_ended', "Ended session #{$session->id}", $session);

            return $session->load(['attendances.student.user', 'timetable.course']);
        });
    }
}
