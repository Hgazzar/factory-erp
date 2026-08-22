<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Core\Messaging\MetaCloudWhatsAppChannel;
use App\Jobs\Nursery\SendNurseryGuardianInviteWhatsAppJob;
use App\Jobs\Nursery\SendNurseryGuardianOtpWhatsAppJob;
use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurseryRenewalReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurserySubscriptionPaidConfirmationWhatsAppJob;
use App\Models\Nursery\NurseryOutboundMessage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class NurseryWhatsAppOutboxService
{
    public function __construct(
        private readonly NurseryWhatsAppNotificationService $whatsapp,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function enqueue(
        int $tenantUserId,
        string $type,
        string $dedupeKey,
        ?string $relatedType,
        ?int $relatedId,
        array $payload = [],
        bool $allowResend = false,
    ): NurseryOutboundMessage {
        $this->assertType($type);

        return DB::transaction(function () use ($tenantUserId, $type, $dedupeKey, $relatedType, $relatedId, $payload, $allowResend): NurseryOutboundMessage {
            $existing = NurseryOutboundMessage::withoutGlobalScopes()
                ->where('dedupe_key', $dedupeKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $this->reuseOrRequeue($existing, $payload, $allowResend);
            }

            try {
                $message = NurseryOutboundMessage::withoutGlobalScopes()->create([
                    'user_id' => $tenantUserId,
                    'type' => $type,
                    'dedupe_key' => $dedupeKey,
                    'status' => NurseryOutboundMessage::STATUS_QUEUED,
                    'attempts' => 0,
                    'payload' => $payload,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                    'queued_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                $existing = NurseryOutboundMessage::withoutGlobalScopes()
                    ->where('dedupe_key', $dedupeKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing === null) {
                    throw new InvalidArgumentException('Nursery outbox unique key raced and disappeared: '.$dedupeKey);
                }

                return $this->reuseOrRequeue($existing, $payload, $allowResend);
            }

            $this->dispatchJob($message);

            return $message;
        });
    }

    public function enqueueSubscriptionMessage(int $tenantUserId, int $subscriptionId, string $type): NurseryOutboundMessage
    {
        return $this->enqueue(
            $tenantUserId,
            $type,
            $type.':'.$tenantUserId.':'.$subscriptionId,
            NurseryOutboundMessage::RELATED_SUBSCRIPTION,
            $subscriptionId,
            ['subscription_id' => $subscriptionId],
            false,
        );
    }

    public function find(int $outboxId): ?NurseryOutboundMessage
    {
        return NurseryOutboundMessage::withoutGlobalScopes()->whereKey($outboxId)->first();
    }

    /**
     * @param  callable(NurseryOutboundMessage): bool  $send
     */
    public function process(int $outboxId, callable $send): void
    {
        $message = $this->claimForProcessing($outboxId);
        if ($message === null) {
            return;
        }

        if ($this->whatsapp->wouldSkipConfig((int) $message->user_id)) {
            $this->markSkippedConfig($message, 'Nursery WhatsApp disabled or feature off');

            return;
        }

        try {
            if ($send($message)) {
                $this->markSent($message, MetaCloudWhatsAppChannel::lastProviderMessageId());

                return;
            }

            $this->markFailed($message, 'WhatsApp send returned false');
        } catch (\Throwable $e) {
            $this->markFailed($message, $e->getMessage());
            Log::error('Nursery WhatsApp outbox send failed', [
                'outbox_id' => $message->id,
                'type' => $message->type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markProcessing(NurseryOutboundMessage $message): void
    {
        $this->claimForProcessing((int) $message->id);
        $message->refresh();
    }

    public function markSent(NurseryOutboundMessage $message, ?string $providerMessageId = null): void
    {
        $values = [
            'status' => NurseryOutboundMessage::STATUS_SENT,
            'sent_at' => now(),
            'failed_at' => null,
            'error' => null,
            'updated_at' => now(),
        ];

        if ($providerMessageId !== null && $providerMessageId !== '') {
            $values['provider_message_id'] = $providerMessageId;
        }

        $this->transitionFromProcessing((int) $message->id, $values);
        $message->refresh();
    }

    public function markFailed(NurseryOutboundMessage $message, string $error): void
    {
        $this->transitionFromProcessing((int) $message->id, [
            'status' => NurseryOutboundMessage::STATUS_FAILED,
            'failed_at' => now(),
            'error' => $error !== '' ? $error : 'failed',
            'updated_at' => now(),
        ]);
        $message->refresh();
    }

    public function markSkippedConfig(NurseryOutboundMessage $message, string $reason = 'skipped_config'): void
    {
        $this->transitionFromProcessing((int) $message->id, [
            'status' => NurseryOutboundMessage::STATUS_SKIPPED_CONFIG,
            'error' => $reason,
            'failed_at' => null,
            'updated_at' => now(),
        ]);
        $message->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reuseOrRequeue(NurseryOutboundMessage $existing, array $payload, bool $allowResend): NurseryOutboundMessage
    {
        if ($existing->isInFlight()) {
            if ($allowResend && $payload !== []) {
                $existing->forceFill(['payload' => $payload])->save();
            }

            return $existing->fresh() ?? $existing;
        }

        if ($existing->status === NurseryOutboundMessage::STATUS_SENT && ! $allowResend) {
            return $existing;
        }

        $existing->forceFill([
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
            'payload' => $payload !== [] ? $payload : $existing->payload,
            'error' => null,
            'queued_at' => now(),
            'sent_at' => null,
            'failed_at' => null,
            'provider_message_id' => null,
        ])->save();

        $this->dispatchJob($existing);

        return $existing->fresh() ?? $existing;
    }

    private function claimForProcessing(int $outboxId): ?NurseryOutboundMessage
    {
        $affected = NurseryOutboundMessage::withoutGlobalScopes()
            ->whereKey($outboxId)
            ->where('status', NurseryOutboundMessage::STATUS_QUEUED)
            ->update([
                'status' => NurseryOutboundMessage::STATUS_PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'error' => null,
                'updated_at' => now(),
            ]);

        if ((int) $affected !== 1) {
            return null;
        }

        return $this->find($outboxId);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function transitionFromProcessing(int $outboxId, array $values): void
    {
        NurseryOutboundMessage::withoutGlobalScopes()
            ->whereKey($outboxId)
            ->where('status', NurseryOutboundMessage::STATUS_PROCESSING)
            ->update($values);
    }

    private function dispatchJob(NurseryOutboundMessage $message): void
    {
        $tenantUserId = (int) $message->user_id;
        $relatedId = (int) ($message->related_id ?? 0);
        $outboxId = (int) $message->id;

        match ($message->type) {
            NurseryOutboundMessage::TYPE_PAYMENT_REMINDER => SendNurseryPaymentReminderWhatsAppJob::dispatch(
                $tenantUserId,
                $relatedId,
                $outboxId,
            ),
            NurseryOutboundMessage::TYPE_RENEWAL_REMINDER => SendNurseryRenewalReminderWhatsAppJob::dispatch(
                $tenantUserId,
                $relatedId,
                $outboxId,
            ),
            NurseryOutboundMessage::TYPE_SUBSCRIPTION_PAID_CONFIRMATION => SendNurserySubscriptionPaidConfirmationWhatsAppJob::dispatch(
                $tenantUserId,
                $relatedId,
                $outboxId,
            ),
            NurseryOutboundMessage::TYPE_GUARDIAN_OTP => SendNurseryGuardianOtpWhatsAppJob::dispatch(
                $tenantUserId,
                $relatedId,
                $outboxId,
            ),
            NurseryOutboundMessage::TYPE_GUARDIAN_INVITE => SendNurseryGuardianInviteWhatsAppJob::dispatch(
                $tenantUserId,
                $relatedId,
                $outboxId,
            ),
            default => throw new InvalidArgumentException('Unsupported nursery outbox type: '.$message->type),
        };
    }

    private function assertType(string $type): void
    {
        $allowed = [
            NurseryOutboundMessage::TYPE_SUBSCRIPTION_PAID_CONFIRMATION,
            NurseryOutboundMessage::TYPE_PAYMENT_REMINDER,
            NurseryOutboundMessage::TYPE_RENEWAL_REMINDER,
            NurseryOutboundMessage::TYPE_GUARDIAN_OTP,
            NurseryOutboundMessage::TYPE_GUARDIAN_INVITE,
        ];

        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported nursery outbox type: '.$type);
        }
    }
}
