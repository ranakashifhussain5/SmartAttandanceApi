<?php

namespace App\Modules\ApplicationTracking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ApplicationAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_key' => $this->field_key,
            'url' => Storage::disk('public')->url($this->path),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'created_at' => $this->created_at,
        ];
    }
}
