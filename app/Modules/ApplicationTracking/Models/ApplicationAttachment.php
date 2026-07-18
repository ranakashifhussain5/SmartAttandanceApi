<?php

namespace App\Modules\ApplicationTracking\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAttachment extends Model
{
    protected $fillable = [
        'application_id', 'application_action_id', 'field_key',
        'uploaded_by_user_id', 'path', 'original_name', 'mime_type',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(ApplicationAction::class, 'application_action_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
