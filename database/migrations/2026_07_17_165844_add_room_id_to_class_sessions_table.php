<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable per-day room override. Null means "use the timetable's room"
     * (the normal weekly schedule is never touched by this column).
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('timetable_id')->constrained('rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
