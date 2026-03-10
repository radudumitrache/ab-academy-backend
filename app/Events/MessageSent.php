<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(private Chat $chat, private Message $message)
    {
        $this->message->load('sender');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'chat_id' => $this->chat->id,
            'message' => [
                'id'          => $this->message->id,
                'content'     => $this->message->content,
                'sender_id'   => $this->message->sender_id,
                'sender_type' => $this->message->sender_type,
                'created_at'  => $this->message->created_at?->toISOString(),
                'sender_role' => match (true) {
                    $sender instanceof \App\Models\Admin   => 'admin',
                    $sender instanceof \App\Models\Student => 'student',
                    $sender instanceof \App\Models\Teacher => 'teacher',
                    default                                => 'unknown',
                },
                'sender' => $sender ? [
                    'id'       => $sender->id,
                    'username' => $sender->username,
                ] : null,
            ],
        ];
    }
}
