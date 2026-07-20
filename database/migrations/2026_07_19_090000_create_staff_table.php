<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Non-teaching administrative office holders (Examination Officer,
     * Registrar, Transport Officer, ...). Unlike teachers, department_id is
     * nullable from the start — these positions are typically
     * university-wide, not department-scoped.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('employee_no', 50)->unique();
            $table->string('designation', 100);
            $table->string('phone', 20)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
