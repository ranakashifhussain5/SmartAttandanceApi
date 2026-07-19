<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar', 'role', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHod(): bool
    {
        return $this->role === 'hod';
    }

    /**
     * Strictly the "teacher" role. HOD is a distinct role that, per the
     * permission matrix, cannot block/unblock students even though an HOD
     * always has an underlying teacher record and can start/end their own
     * lectures like a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Non-teaching administrative office holder (Examination Officer,
     * Registrar, Transport Officer, ...) — never granted attendance-module
     * access, only Application Tracking (which is open to any authenticated
     * user, so no special-casing is needed there).
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }
}
