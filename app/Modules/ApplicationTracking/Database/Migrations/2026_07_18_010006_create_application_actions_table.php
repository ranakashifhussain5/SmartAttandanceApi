<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', [
                'submitted', 'approved', 'rejected', 'forwarded', 'commented', 'resubmitted', 'cancelled',
            ]);
            $table->text('remarks')->nullable();
            $table->foreignId('forwarded_to_office_id')->nullable()->constrained('offices')->nullOnDelete();
            // Captures the submitted form state on submitted/resubmitted rows,
            // giving a full revision history without a separate versions table.
            $table->json('form_data_snapshot')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_actions');
    }
};
