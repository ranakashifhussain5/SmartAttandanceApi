<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrects a naming mistake in 2026_07_18_010008: application_actions
     * (and the "application_{event}" notification type it feeds) use
     * past-tense completed-event names (approved/rejected/forwarded/
     * commented), not the present-tense request verbs (approve/reject/
     * forward/comment) 010008 mistakenly widened the enum with. Those
     * present-tense values were never actually written to any row, so this
     * is a safe forward fix rather than a data migration.
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
                'application_submitted',
                'application_approve',
                'application_reject',
                'application_forward',
                'application_comment',
                'application_resubmitted',
                'application_cancelled',
            ])->change();
        });
    }
};
