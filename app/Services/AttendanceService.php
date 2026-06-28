<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Student;

class AttendanceService
{
    public function __construct(
        private NotificationService $notifications,
        private AuditLogService $auditLog,
    ) {}

    public function mark(ClassSession $session, Student $student, string $wifiMac): Attendance
    {
        if ($session->status !== 'active') {
            throw new BusinessException('This session is not active.');
        }

        if ($student->is_blocked) {
            throw new BusinessException('You are blocked from marking attendance.', 403);
        }

        if ($session->timetable->batch_id !== $student->batch_id) {
            throw new BusinessException('This session does not belong to your batch.', 403);
        }

        $attendance = Attendance::where('session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $attendance) {
            throw new BusinessException('No attendance record was found for you in this session.', 404);
        }

        if ($attendance->status === 'present') {
            throw new BusinessException('You have already marked attendance for this session.', 409);
        }

        $roomMac = strtoupper($session->timetable->room->wifi_mac);

        if (strtoupper($wifiMac) !== $roomMac) {
            throw new BusinessException('WiFi network does not match the classroom network.');
        }

        $attendance->update([
            'wifi_mac_detected' => strtoupper($wifiMac),
            'status' => 'present',
            'marked_at' => now(),
        ]);

        $this->notifications->send(
            $student->user_id,
            'Attendance Marked',
            'You have been marked present for this session.',
            'attendance_marked',
            ['session_id' => $session->id, 'status' => 'present'],
        );

        $this->auditLog->log($student->user_id, 'attendance_marked', "Marked present for session #{$session->id}", $attendance);

        return $attendance->load('session.timetable');
    }
}
