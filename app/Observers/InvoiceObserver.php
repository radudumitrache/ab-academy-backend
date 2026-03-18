<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\NotificationService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        if (!$invoice->student_id) {
            return;
        }

        $due = $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'TBD';

        $message = "A new invoice '{$invoice->title}' for {$invoice->value} {$invoice->currency} has been issued, due on {$due}.";

        NotificationService::notify($invoice->student_id, $message, 'Admin', 'Payment');
        NotificationService::notifyByEmail($invoice->student_id, $message, 'Payment');
    }
}
