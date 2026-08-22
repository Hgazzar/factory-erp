<?php

declare(strict_types=1);

namespace App\Jobs\Nursery;

use App\Models\Nursery\NurseryOutboundMessage;
use App\Services\Nursery\NurseryWhatsAppNotificationService;
use App\Services\Nursery\NurseryWhatsAppOutboxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendNurseryGuardianOtpWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantUserId,
        public readonly int $guardianId,
        public readonly ?int $outboxId = null,
    ) {}

    public function handle(NurseryWhatsAppOutboxService $outbox, NurseryWhatsAppNotificationService $whatsapp): void
    {
        if ($this->outboxId === null) {
            return;
        }

        $outbox->process($this->outboxId, function (NurseryOutboundMessage $message) use ($whatsapp): bool {
            $payload = is_array($message->payload) ? $message->payload : [];
            $phone = trim((string) ($payload['phone'] ?? ''));
            $text = trim((string) ($payload['message'] ?? ''));

            if ($phone === '' || $text === '') {
                return false;
            }

            return $whatsapp->sendTextMessage($phone, $text);
        });
    }
}
