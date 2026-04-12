<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendingCommissionsNotification extends Notification
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
            'category' => 'commissions',
            'icon' => 'currency',
            'title' => 'عمولات في انتظار الاعتماد',
            'body' => "هناك {$this->count} عمولة في انتظار الاعتماد.",
            'url' => route('sales.commissions.index'),
            'count' => $this->count,
        ];
    }
}

