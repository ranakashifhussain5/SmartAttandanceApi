<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;

class StudentPolicy
{
    public function block(User $user, Student $student): bool
    {
        if (! $user->isTeacher() || ! $user->teacher) {
            return false;
        }

        return Timetable::where('teacher_id', $user->teacher->id)
            ->where('batch_id', $student->batch_id)
            ->exists();
    }

    public function unblock(User $user, Student $student): bool
    {
        return $this->block($user, $student);
    }
}
