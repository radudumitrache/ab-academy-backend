<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Send an email notification to one or many users (skips users with no email).
     *
     * @param int|int[] $userIds
     * @param string    $message
     * @param string    $type    One of Notification::TYPES
     */
    public static function notifyByEmail(int|array $userIds, string $message, string $type): void
    {
        $ids = array_unique(array_filter((array) $userIds));

        if (empty($ids)) {
            return;
        }

        $users = User::whereIn('id', $ids)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email']);

        foreach ($users as $user) {
            Mail::to($user->email)->send(new NotificationMail($type, $message));
        }
    }
}
