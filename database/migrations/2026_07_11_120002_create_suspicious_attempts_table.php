<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspicious_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('fail_reason');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('fail_reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspicious_attempts');
    }
};
