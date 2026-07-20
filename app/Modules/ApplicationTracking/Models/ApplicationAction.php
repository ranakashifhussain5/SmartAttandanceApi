<?php

namespace App\Modules\ApplicationTracking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationAction extends Model
{
    protected $fillable = [
        'application_id', 'workflow_step_id', 'actor_user_id', 'action',
        'remarks', 'forwarded_to_office_id', 'form_data_snapshot',
    ];

    protected function casts(): array
    {
        return ['form_data_snapshot' => 'array'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function forwardedToOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'forwarded_to_office_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ApplicationAttachment::class);
    }
}
