<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Timetable;

class StudentBlockService
{
    public function __construct(
        private NotificationService $notifications,
        private AuditLogService $auditLog,
    ) {}

    public function block(Student $student, Teacher $teacher): Student
    {
        $this->ensureTeachesStudent($student, $teacher);

        if ($student->is_blocked) {
            throw new BusinessException('Student is already blocked.', 409);
        }

        $student->update([
            'is_blocked' => true,
            'blocked_by_user_id' => $teacher->user_id,
            'blocked_at' => now(),
        ]);

        $this->notifications->send(
            $student->user_id,
            'Account Blocked',
            'You have been blocked from marking attendance by your teacher.',
            'student_blocked',
        );

        $this->auditLog->log($teacher->user_id, 'student_blocked', "Blocked student #{$student->id}", $student);

        return $student;
    }

    public function unblock(Student $student, Teacher $teacher): Student
    {
        $this->ensureTeachesStudent($student, $teacher);

        if (! $student->is_blocked) {
            throw new BusinessException('Student is not blocked.', 409);
        }

        $student->update([
            'is_blocked' => false,
            'blocked_by_user_id' => null,
            'blocked_at' => null,
        ]);

        $this->notifications->send(
            $student->user_id,
            'Account Unblocked',
            'You have been unblocked and can mark attendance again.',
            'student_unblocked',
        );

        $this->auditLog->log($teacher->user_id, 'student_unblocked', "Unblocked student #{$student->id}", $student);

        return $student;
    }

    private function ensureTeachesStudent(Student $student, Teacher $teacher): void
    {
        $teachesBatch = Timetable::where('teacher_id', $teacher->id)
            ->where('batch_id', $student->batch_id)
            ->exists();

        if (! $teachesBatch) {
            throw new BusinessException('You do not teach this student\'s batch.', 403);
        }
    }
}
