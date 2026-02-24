<?php

namespace App\Observers;

use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;

class MessageObserver
{
    /**
     * Notify the other party only when an admin sends a message.
     */
    public function created(Message $message): void
    {
        $sender = User::find($message->sender_id);

        if (!$sender || !$sender->isAdmin()) {
            return;
        }

        $chat = $message->chat()->with(['student'])->first();

        if (!$chat || !$chat->student_id) {
            return;
        }

        NotificationService::notify(
            $chat->student_id,
            "You have a new message from {$sender->username}.",
            'Admin',
            'Message'
        );
    }
}
