<?php

namespace App\Modules\ApplicationTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_template_id', 'step_order', 'name', 'approver_type',
        'approver_office_id', 'on_approve_next_step_id', 'on_reject_action', 'allow_forward',
    ];

    protected function casts(): array
    {
        return ['allow_forward' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'approver_office_id');
    }

    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(self::class, 'on_approve_next_step_id');
    }
}
