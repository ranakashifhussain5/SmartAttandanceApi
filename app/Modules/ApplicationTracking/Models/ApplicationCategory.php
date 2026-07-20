<?php

namespace App\Modules\ApplicationTracking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationCategory extends Model
{
    protected $fillable = [
        'name', 'description', 'form_schema', 'workflow_template_id',
        'applicant_roles', 'allow_multiple_active', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'form_schema' => 'array',
            'applicant_roles' => 'array',
            'allow_multiple_active' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
