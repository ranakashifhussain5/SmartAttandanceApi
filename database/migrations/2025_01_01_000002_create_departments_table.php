<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->unsignedBigInteger('hod_teacher_id')->nullable();
            $table->timestamps();

            $table->index('hod_teacher_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
