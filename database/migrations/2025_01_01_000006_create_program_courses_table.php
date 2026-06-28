<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('course_code', 20);
            $table->string('course_title', 255);
            $table->unsignedTinyInteger('credit_hours');
            $table->timestamps();

            $table->index('course_code');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_courses');
    }
};
