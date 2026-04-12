<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContractReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public int $count)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'contracts',
            'icon' => 'clock',
            'title' => 'عقود تقترب من الانتهاء',
            'body' => "هناك {$this->count} عقد ينتهي خلال أيام التذكير المحددة.",
            'url' => route('sales.contracts.index'),
            'count' => $this->count,
        ];
    }
}

