<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The core notifications table restricts `type` to a fixed DB-level
     * enum (attendance_started, attendance_marked, session_ended,
     * student_blocked, student_unblocked). This module reuses that same
     * table via NotificationService rather than building a parallel
     * notifications system, so it needs its own type values recognized too.
     * Purely additive — every existing value stays valid, nothing removed.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'attendance_started',
                'attendance_marked',
                'session_ended',
                'student_blocked',
                'student_unblocked',
                'application_submitted',
                'application_approved',
                'application_rejected',
                'application_forwarded',
                'application_commented',
                'application_resubmitted',
                'application_cancelled',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'attendance_started',
                'attendance_marked',
                'session_ended',
                'student_blocked',
                'student_unblocked',
            ])->change();
        });
    }
};
