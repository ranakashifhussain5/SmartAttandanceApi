<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentService
{
    public function create(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'student',
                'avatar' => $data['avatar'] ?? null,
            ]);

            return Student::create([
                'user_id' => $user->id,
                'registration_no' => $data['registration_no'],
                'department_id' => $data['department_id'],
                'batch_id' => $data['batch_id'],
                'phone' => $data['phone'] ?? null,
            ]);
        });
    }

    public function update(Student $student, array $data): Student
    {
        return DB::transaction(function () use ($student, $data) {
            $student->user->fill(array_intersect_key($data, array_flip(['name', 'email', 'status'])))->save();
            $student->fill(array_intersect_key($data, array_flip(['registration_no', 'department_id', 'batch_id', 'phone'])))->save();

            return $student;
        });
    }
}
