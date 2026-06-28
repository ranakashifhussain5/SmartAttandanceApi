<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function send(int $userId, string $title, string $message, string $type, array $relatedData = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_data' => $relatedData,
        ]);
    }

    public function sendMany(iterable $userIds, string $title, string $message, string $type, array $relatedData = []): void
    {
        $rows = Collection::make($userIds)->map(fn (int $userId) => [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_data' => json_encode($relatedData),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Notification::insert($rows->all());
    }
}
