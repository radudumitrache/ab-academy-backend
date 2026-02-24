<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create a notification for one or many users.
     *
     * @param int|int[] $userIds
     * @param string    $message
     * @param string    $source  One of Notification::SOURCES
     * @param string    $type    One of Notification::TYPES
     */
    public static function notify(int|array $userIds, string $message, string $source, string $type): void
    {
        $ids = array_unique(array_filter((array) $userIds));

        foreach ($ids as $userId) {
            Notification::create([
                'notification_owner'   => $userId,
                'notification_message' => $message,
                'notification_time'    => now(),
                'is_seen'              => false,
                'notification_source'  => $source,
                'notification_type'    => $type,
            ]);
        }
    }
}
