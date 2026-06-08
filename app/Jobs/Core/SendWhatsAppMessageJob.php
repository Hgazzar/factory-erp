<?php

declare(strict_types=1);

namespace App\Jobs\Core;

use App\Core\Messaging\WhatsAppChannelFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $profile,
        public readonly string $toPhone,
        public readonly string $message,
        public readonly bool $previewUrl = true,
    ) {}

    public function handle(WhatsAppChannelFactory $channels): void
    {
        $channels->forProfile($this->profile)->sendText(
            $this->toPhone,
            $this->message,
            $this->previewUrl,
        );
    }
}
