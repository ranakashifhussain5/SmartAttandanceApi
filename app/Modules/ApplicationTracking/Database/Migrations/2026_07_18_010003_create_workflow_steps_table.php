<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_template_id')->constrained('workflow_templates')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('name');
            $table->enum('approver_type', ['office', 'applicant_department_hod']);
            $table->foreignId('approver_office_id')->nullable()->constrained('offices')->nullOnDelete();
            // Self-referencing "next step on approval" — null means this step
            // is final; approving here finishes the application as approved.
            // Added as a plain column here, constrained below once the table
            // exists, mirroring the safe two-step pattern already used in
            // 2026_06_29_033608_make_department_id_nullable_on_teachers_table.
            $table->unsignedBigInteger('on_approve_next_step_id')->nullable();
            $table->enum('on_reject_action', ['terminate', 'return_to_applicant'])->default('terminate');
            $table->boolean('allow_forward')->default(false);
            $table->timestamps();
        });

        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreign('on_approve_next_step_id')->references('id')->on('workflow_steps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['on_approve_next_step_id']);
        });

        Schema::dropIfExists('workflow_steps');
    }
};
