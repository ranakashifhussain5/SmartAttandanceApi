<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A simple, directly-queryable notification record (not Laravel's
 * polymorphic database notifications) matching the spec's schema.
 */
class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'type', 'related_data', 'is_read'];

    protected function casts(): array
    {
        return [
            'related_data' => 'array',
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
