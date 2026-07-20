<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widens the role enum to add "staff" — non-teaching administrative
     * office holders (Examination Officer, Registrar, Transport Officer,
     * ...). Uses the portable schema-builder ->change() (no raw per-driver
     * SQL) so it applies cleanly on both SQLite (dev/test) and MySQL (prod).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'hod', 'teacher', 'student', 'staff'])
                ->default('student')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'hod', 'teacher', 'student'])
                ->default('student')
                ->change();
        });
    }
};
