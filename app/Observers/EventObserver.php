<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\NotificationService;

class EventObserver
{
    public function created(Event $event): void
    {
        $date = $event->event_date ? $event->event_date->format('Y-m-d') : 'TBD';

        NotificationService::notify(
            $this->resolveRecipients($event),
            "A new event '{$event->title}' has been scheduled for {$date}.",
            'Admin',
            'Schedule'
        );
    }

    public function updated(Event $event): void
    {
        $date = $event->event_date ? $event->event_date->format('Y-m-d') : 'TBD';

        NotificationService::notify(
            $this->resolveRecipients($event),
            "The event '{$event->title}' has been updated. Date: {$date}.",
            'Admin',
            'Schedule'
        );
    }

    public function deleting(Event $event): void
    {
        NotificationService::notify(
            $this->resolveRecipients($event),
            "The event '{$event->title}' has been cancelled.",
            'Admin',
            'Schedule'
        );
    }

    private function resolveRecipients(Event $event): array
    {
        $ids = $event->guests ?? [];

        // Also notify the organizer
        if ($event->event_organizer) {
            $ids[] = $event->event_organizer;
        }

        return array_unique(array_filter($ids));
    }
}
