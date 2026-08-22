<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Jobs\Nursery\SendNurseryGuardianInviteWhatsAppJob;
use App\Jobs\Nursery\SendNurseryGuardianOtpWhatsAppJob;
use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryOutboundMessage;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurseryPortalInviteService;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Nursery\NurseryWhatsAppNotifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryWhatsAppOutboxTest extends NurseryTestCase
{
    #[Test]
    public function enqueue_creates_queued_row_and_dispatches_job_with_outbox_id(): void
    {
        Queue::fake();
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryPaymentReminderWhatsAppJob::class, function (SendNurseryPaymentReminderWhatsAppJob $job) use ($subscription): bool {
            return $job->subscriptionId === (int) $subscription->id
                && $job->outboxId !== null;
        });

        $this->assertDatabaseHas('nursery_outbound_messages', [
            'user_id' => $this->tenant->id,
            'type' => NurseryOutboundMessage::TYPE_PAYMENT_REMINDER,
            'related_type' => NurseryOutboundMessage::RELATED_SUBSCRIPTION,
            'related_id' => $subscription->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
            'dedupe_key' => NurseryOutboundMessage::TYPE_PAYMENT_REMINDER.':'.$this->tenant->id.':'.$subscription->id,
        ]);
    }

    #[Test]
    public function duplicate_enqueue_does_not_create_second_row_or_job(): void
    {
        Queue::fake();
        [$child, $plan] = $this->seedChildAndPlan();
        $this->makeUnpaidSubscription($child, $plan);

        $service = app(NurserySubscriptionService::class);
        $service->sendPaymentReminders((int) $this->tenant->id);
        $service->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryPaymentReminderWhatsAppJob::class, 1);
        $this->assertSame(1, NurseryOutboundMessage::withoutGlobalScopes()->count());
    }

    #[Test]
    public function job_success_marks_sent_and_stamps_reminder(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $this->enableNurseryWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        $this->assertDatabaseHas('nursery_outbound_messages', [
            'related_id' => $subscription->id,
            'type' => NurseryOutboundMessage::TYPE_PAYMENT_REMINDER,
            'status' => NurseryOutboundMessage::STATUS_SENT,
        ]);
        $this->assertNotNull($subscription->fresh()->payment_reminder_sent_at);
        $this->assertSame(
            'wamid.1',
            NurseryOutboundMessage::withoutGlobalScopes()->first()?->provider_message_id,
        );
    }

    #[Test]
    public function disabled_nursery_whatsapp_marks_skipped_config_without_stamping_sent_at(): void
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
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);
    }

    #[Test]
    public function channel_failure_marks_failed_without_stamping_sent_at(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);
        $this->enableNurseryWhatsApp();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'fail']], 500),
        ]);

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        $this->assertDatabaseHas('nursery_outbound_messages', [
            'related_id' => $subscription->id,
            'status' => NurseryOutboundMessage::STATUS_FAILED,
        ]);
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);
    }

    #[Test]
    public function portal_otp_writes_guardian_otp_outbox_and_login_still_works(): void
    {
        Queue::fake();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'أم OTP',
            'phone' => '0551234567',
        ]);

        $this->post(route('nursery.portal.otp.request', ['tenant_slug' => 'test-nursery']), [
            'phone' => '0551234567',
        ])->assertRedirect()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'ستتم جدولة')
                && ! str_contains($message, 'تم إرسال رمز التحقق'));

        Queue::assertPushed(SendNurseryGuardianOtpWhatsAppJob::class);
        $this->assertDatabaseHas('nursery_outbound_messages', [
            'type' => NurseryOutboundMessage::TYPE_GUARDIAN_OTP,
            'related_id' => $guardian->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
        ]);

        $this->post(route('nursery.portal.otp.verify', ['tenant_slug' => 'test-nursery']), [
            'phone' => '0551234567',
            'otp' => '123456',
        ])->assertRedirect(route('nursery.portal.home', ['tenant_slug' => 'test-nursery']));
    }

    #[Test]
    public function portal_invite_writes_guardian_invite_outbox_without_claiming_delivery(): void
    {
        Queue::fake();

        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي دعوة',
            'phone' => '0559998888',
        ]);

        $result = app(NurseryPortalInviteService::class)->sendInviteToGuardian(
            (int) $this->tenant->id,
            $guardian,
        );

        $this->assertTrue($result['sent']);
        $this->assertStringContainsString('تمت جدولة', $result['message']);
        $this->assertStringNotContainsString('وهمي', $result['message']);
        $this->assertStringNotContainsString('تم إرسال رابط الدعوة', $result['message']);

        Queue::assertPushed(SendNurseryGuardianInviteWhatsAppJob::class);
        $this->assertDatabaseHas('nursery_outbound_messages', [
            'type' => NurseryOutboundMessage::TYPE_GUARDIAN_INVITE,
            'related_id' => $guardian->id,
            'status' => NurseryOutboundMessage::STATUS_QUEUED,
        ]);
    }

    #[Test]
    public function dispatch_is_not_treated_as_sent_when_notify_is_called_directly_with_disabled_channel(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();
        $subscription = $this->makeUnpaidSubscription($child, $plan);

        config([
            'nursery.whatsapp.enabled' => false,
            'nursery.whatsapp.access_token' => '',
            'nursery.whatsapp.phone_number_id' => '',
        ]);

        $sent = app(NurseryWhatsAppNotifier::class)->notifyPaymentReminder(
            (int) $this->tenant->id,
            (int) $subscription->id,
        );

        $this->assertFalse($sent);
        $this->assertNull($subscription->fresh()->payment_reminder_sent_at);
    }

    /**
     * @return array{0: Child, 1: SubscriptionPlan}
     */
    private function seedChildAndPlan(): array
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي Outbox',
            'phone' => '0501234567',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل Outbox',
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
