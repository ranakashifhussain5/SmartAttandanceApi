<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('batch_name', 50);
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->unsignedTinyInteger('semester');
            $table->enum('shift', ['Morning', 'Evening']);
            $table->timestamps();

            $table->index('batch_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
