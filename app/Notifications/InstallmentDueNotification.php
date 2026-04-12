<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstallmentDueNotification extends Notification
{
    use Queueable;

    public function __construct(public float $amount)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'installments',
            'icon' => 'calendar',
            'title' => 'أقساط مستحقة التحصيل',
            'body' => 'هناك أقساط مستحقة لم يتم تحصيلها بعد بقيمة تقريبية SAR ' . number_format($this->amount, 2),
            'url' => route('sales.installments.index'),
            'amount' => $this->amount,
        ];
    }
}

