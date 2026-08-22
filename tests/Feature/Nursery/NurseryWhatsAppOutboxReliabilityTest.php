<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Jobs\Nursery\SendNurseryGuardianOtpWhatsAppJob;
use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurserySubscriptionPaidConfirmationWhatsAppJob;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryOutboundMessage;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Nursery\NurseryWhatsAppNotifier;
use App\Services\Nursery\NurseryWhatsAppOutboxService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryWhatsAppOutboxReliabilityTest extends NurseryTestCase
{
    #[Test]
    public function two_dispatch_attempts_create_one_outbox_and_one_job(): void
    {
        Queue::fake();
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $service = app(NurserySubscriptionService::class);

        $service->sendPaymentReminders((int) $this->tenant->id);
        $service->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryPaymentReminderWhatsAppJob::class, 1);
        $this->assertSame(1, NurseryOutboundMessage::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('nursery_outbound_messages', [
            'related_id' => $subscription->id,
            'dedupe_key' => NurseryOutboundMessage::TYPE_PAYMENT_REMINDER.':'.$this->tenant->id.':'.$subscription->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
        ]);
    }

    #[Test]
    public function two_workers_for_same_outbox_send_once(): void
    {
        Queue::fake();
        $this->enableNurseryWhatsApp();
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        $outboxId = (int) NurseryOutboundMessage::withoutGlobalScopes()->firstOrFail()->id;
        $sends = 0;
        $outbox = app(NurseryWhatsAppOutboxService::class);

        $outbox->process($outboxId, function () use ($outbox, $outboxId, &$sends): bool {
            $sends++;
            $outbox->process($outboxId, function () use (&$sends): bool {
                $sends++;

                return true;
            });

            return true;
        });

        $this->assertSame(1, $sends);
        $this->assertSame(
            NurseryOutboundMessage::STATUS_SENT,
            NurseryOutboundMessage::withoutGlobalScopes()->whereKey($outboxId)->value('status'),
        );
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);
    }

    #[Test]
    public function failed_send_can_retry_and_succeed_once(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $this->enableNurseryWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'fail']], 500)
                ->push(['messages' => [['id' => 'wamid.retry']]], 200),
        ]);

        $service = app(NurserySubscriptionService::class);
        $service->sendPaymentReminders((int) $this->tenant->id);

        $this->assertDatabaseHas('nursery_outbound_messages', [
            'related_id' => $subscription->id,
            'status' => NurseryOutboundMessage::STATUS_FAILED,
        ]);
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);

        $service->sendPaymentReminders((int) $this->tenant->id);

        $row = NurseryOutboundMessage::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(NurseryOutboundMessage::STATUS_SENT, $row->status);
        $this->assertSame('wamid.retry', $row->provider_message_id);
        $this->assertNotNull($row->sent_at);
        $this->assertNull($row->failed_at);
        $this->assertNotNull($subscription->fresh()->payment_reminder_sent_at);
        $this->assertSame(1, NurseryOutboundMessage::withoutGlobalScopes()->count());
        Http::assertSentCount(2);
    }

    #[Test]
    public function disabled_whatsapp_is_skipped_config_without_sent_timestamp(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        config([
            'nursery.whatsapp.enabled' => false,
            'nursery.whatsapp.access_token' => '',
            'nursery.whatsapp.phone_number_id' => '',
        ]);

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        $this->assertDatabaseHas('nursery_outbound_messages', [
            'related_id' => $subscription->id,
            'status' => NurseryOutboundMessage::STATUS_SKIPPED_CONFIG,
        ]);
        $this->assertNull(NurseryOutboundMessage::withoutGlobalScopes()->first()?->sent_at);
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);
    }

    #[Test]
    public function duplicate_paid_confirmation_uses_one_dedupe_key(): void
    {
        Queue::fake();
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $notifier = app(NurseryWhatsAppNotifier::class);

        $notifier->dispatchSubscriptionPaidConfirmation((int) $this->tenant->id, (int) $subscription->id);
        $notifier->dispatchSubscriptionPaidConfirmation((int) $this->tenant->id, (int) $subscription->id);

        Queue::assertPushed(SendNurserySubscriptionPaidConfirmationWhatsAppJob::class, 1);
        $this->assertSame(1, NurseryOutboundMessage::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('nursery_outbound_messages', [
            'type' => NurseryOutboundMessage::TYPE_SUBSCRIPTION_PAID_CONFIRMATION,
            'dedupe_key' => NurseryOutboundMessage::TYPE_SUBSCRIPTION_PAID_CONFIRMATION.':'.$this->tenant->id.':'.$subscription->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
        ]);
    }

    #[Test]
    public function duplicate_in_flight_otp_does_not_dispatch_second_job(): void
    {
        Queue::fake();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي OTP مكرر',
            'phone' => '0551234567',
        ]);

        $this->post(route('nursery.portal.otp.request', ['tenant_slug' => 'test-nursery']), [
            'phone' => '0551234567',
        ])->assertRedirect();
        $this->post(route('nursery.portal.otp.request', ['tenant_slug' => 'test-nursery']), [
            'phone' => '0551234567',
        ])->assertRedirect();

        Queue::assertPushed(SendNurseryGuardianOtpWhatsAppJob::class, 1);
        $this->assertSame(1, NurseryOutboundMessage::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('nursery_outbound_messages', [
            'type' => NurseryOutboundMessage::TYPE_GUARDIAN_OTP,
            'related_id' => $guardian->id,
            'dedupe_key' => NurseryOutboundMessage::TYPE_GUARDIAN_OTP.':'.$this->tenant->id.':'.$guardian->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
        ]);
    }

    #[Test]
    public function stale_failed_transition_cannot_overwrite_sent(): void
    {
        Queue::fake();
        $this->enableNurseryWhatsApp();
        [$child, $plan] = $this->seedChildAndPlan();
        $this->makeUnpaidSubscription($child, $plan);
        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        $outbox = app(NurseryWhatsAppOutboxService::class);
        $message = NurseryOutboundMessage::withoutGlobalScopes()->firstOrFail();
        $outbox->process((int) $message->id, fn (): bool => true);

        $stale = $message->fresh();
        $this->assertSame(NurseryOutboundMessage::STATUS_SENT, $stale?->status);

        $outbox->markFailed($stale, 'late worker');

        $this->assertSame(
            NurseryOutboundMessage::STATUS_SENT,
            NurseryOutboundMessage::withoutGlobalScopes()->whereKey($stale->id)->value('status'),
        );
        $this->assertNotNull($stale->fresh()->sent_at);
        $this->assertNull($stale->fresh()->failed_at);
    }

    #[Test]
    public function legacy_job_without_outbox_id_still_notifies(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $this->enableNurseryWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.legacy']]], 200),
        ]);

        (new SendNurseryPaymentReminderWhatsAppJob(
            (int) $this->tenant->id,
            (int) $subscription->id,
            null,
        ))->handle(
            app(NurseryWhatsAppNotifier::class),
            app(NurseryWhatsAppOutboxService::class),
        );

        $this->assertNotNull($subscription->fresh()->payment_reminder_sent_at);
        $this->assertSame(0, NurseryOutboundMessage::withoutGlobalScopes()->count());
        Http::assertSentCount(1);
    }

    /**
     * @return array{0: Child, 1: SubscriptionPlan}
     */
    private function seedChildAndPlan(): array
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي Reliability',
            'phone' => '0501234567',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل Reliability',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($plan);

        return [$child, $plan];
    }

    private function makeUnpaidSubscription(Child $child, SubscriptionPlan $plan): Subscription
    {
        return Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_UNPAID,
        ]);
    }

    private function enableNurseryWhatsApp(): void
    {
        config([
            'nursery.whatsapp.enabled' => true,
            'nursery.whatsapp.access_token' => 'test-token',
            'nursery.whatsapp.phone_number_id' => 'phone-id-99',
            'nursery.whatsapp.api_version' => 'v21.0',
            'nursery.whatsapp.default_country_code' => '966',
        ]);
    }
}
