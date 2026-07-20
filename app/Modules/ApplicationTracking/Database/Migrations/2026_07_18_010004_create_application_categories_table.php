<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // Dynamic form field definitions: [{key,label,type,required,...}]
            $table->json('form_schema');
            $table->foreignId('workflow_template_id')->constrained('workflow_templates')->restrictOnDelete();
            // null = open to any authenticated user; otherwise an array of
            // allowed User::role values, e.g. ["student"] or ["teacher","hod"].
            $table->json('applicant_roles')->nullable();
            $table->boolean('allow_multiple_active')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_categories');
    }
};
