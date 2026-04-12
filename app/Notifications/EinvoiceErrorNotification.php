<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EinvoiceErrorNotification extends Notification
{
    use Queueable;

    public function __construct(public string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'einvoice',
            'icon' => 'warning',
            'title' => 'خطأ في الفوترة الإلكترونية',
            'body' => $this->message,
            'url' => route('sales.einvoice.settings.edit'),
        ];
    }
}

