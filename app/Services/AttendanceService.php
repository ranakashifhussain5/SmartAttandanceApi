<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Student;
use App\Models\SuspiciousAttempt;

class AttendanceService
{
    public function __construct(
        private NotificationService $notifications,
        private AuditLogService $auditLog,
    ) {}

    public function mark(
        ClassSession $session,
        Student $student,
        string $detectedUuid,
        int $detectedMajor,
        int $rssi,
        float $latitude,
        float $longitude,
    ): Attendance {
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

        $room = $session->timetable->room;

        $this->assertWithinCampusGeofence($session, $student, $latitude, $longitude);
        $this->assertRssiStrongEnough($session, $student, $room, $rssi);
        $this->assertCorrectBeaconDetected($session, $student, $room, $detectedUuid, $detectedMajor);

        $attendance->update([
            'detected_uuid' => $detectedUuid,
            'detected_major' => $detectedMajor,
            'rssi' => $rssi,
            'latitude' => $latitude,
            'longitude' => $longitude,
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

    private function assertWithinCampusGeofence(ClassSession $session, Student $student, float $latitude, float $longitude): void
    {
        $campusLat = config('attendance.campus_latitude');
        $campusLng = config('attendance.campus_longitude');

        if ($campusLat === null || $campusLng === null) {
            return; // Geofence not configured yet — skip rather than block every submission.
        }

        $distance = $this->distanceMeters($latitude, $longitude, (float) $campusLat, (float) $campusLng);

        if ($distance > (float) config('attendance.geofence_radius_meters')) {
            $this->logSuspicious($session, $student, 'gps_outside_geofence', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_meters' => round($distance),
            ]);

            throw new BusinessException('You are outside the campus boundary.');
        }
    }

    private function assertRssiStrongEnough(ClassSession $session, Student $student, Room $room, int $rssi): void
    {
        $threshold = $room->rssi_threshold ?? config('attendance.default_rssi_threshold');

        if ($rssi < $threshold) {
            $this->logSuspicious($session, $student, 'rssi_too_weak', [
                'rssi' => $rssi,
                'threshold' => $threshold,
            ]);

            throw new BusinessException('BLE signal too weak — move closer to the classroom beacon.');
        }
    }

    private function assertCorrectBeaconDetected(ClassSession $session, Student $student, Room $room, string $detectedUuid, int $detectedMajor): void
    {
        if (! hash_equals(strtoupper($room->beacon_uuid), strtoupper($detectedUuid))) {
            $this->logSuspicious($session, $student, 'wrong_beacon_uuid', [
                'detected_uuid' => $detectedUuid,
                'expected_uuid' => $room->beacon_uuid,
            ]);

            throw new BusinessException('BLE beacon is not a recognized attendance beacon.');
        }

        if ($room->beacon_major === null || $detectedMajor !== $room->beacon_major) {
            $this->logSuspicious($session, $student, 'wrong_room_beacon', [
                'detected_major' => $detectedMajor,
                'expected_major' => $room->beacon_major,
            ]);

            throw new BusinessException('BLE beacon does not match this classroom.');
        }
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function logSuspicious(ClassSession $session, Student $student, string $reason, array $payload): void
    {
        SuspiciousAttempt::create([
            'session_id' => $session->id,
            'student_id' => $student->id,
            'fail_reason' => $reason,
            'payload' => $payload,
        ]);
    }
}
