<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_category_id')->constrained('application_categories')->restrictOnDelete();
            $table->foreignId('applicant_user_id')->constrained('users')->cascadeOnDelete();
            // Locked at submission; the only way to change it is reject -> resubmit.
            $table->json('form_data');
            // Snapshot of which template was active at submission time.
            $table->foreignId('workflow_template_id')->constrained('workflow_templates')->restrictOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->enum('status', ['pending', 'returned_for_revision', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['applicant_user_id', 'status']);
            $table->index(['application_category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
