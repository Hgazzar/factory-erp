<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurseryRenewalReminderWhatsAppJob;
use App\Jobs\Nursery\SendNurserySubscriptionPaidConfirmationWhatsAppJob;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Nursery\NurseryWhatsAppNotifier;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryWhatsAppJobTest extends NurseryTestCase
{
    #[Test]
    public function paid_subscription_creation_dispatches_whatsapp_confirmation_job(): void
    {
        Queue::fake();

        [$child, $plan] = $this->seedChildAndPlan();

        $result = app(NurserySubscriptionService::class)->create((int) $this->tenant->id, [
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'is_paid' => true,
        ]);

        Queue::assertPushed(SendNurserySubscriptionPaidConfirmationWhatsAppJob::class);
        $this->assertFalse($result['whatsapp_sent']);
        $this->assertTrue($result['whatsapp_dispatched']);
    }

    #[Test]
    public function payment_reminders_dispatch_whatsapp_jobs(): void
    {
        Queue::fake();

        [$child, $plan] = $this->seedChildAndPlan();

        Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        config([
            'nursery.whatsapp.enabled' => false,
            'nursery.whatsapp.access_token' => null,
            'nursery.whatsapp.phone_number_id' => null,
        ]);

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryPaymentReminderWhatsAppJob::class);
    }

    #[Test]
    public function renewal_reminders_dispatch_whatsapp_jobs(): void
    {
        Queue::fake();

        [$child, $plan] = $this->seedChildAndPlan();

        Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->subMonth()->format('Y-m-d'),
            'ends_on' => now()->addDays(7)->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => true,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        config([
            'nursery.whatsapp.enabled' => false,
            'nursery.whatsapp.access_token' => null,
            'nursery.whatsapp.phone_number_id' => null,
        ]);

        app(NurserySubscriptionService::class)->sendRenewalReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryRenewalReminderWhatsAppJob::class);
    }

    #[Test]
    public function disabled_nursery_whatsapp_does_not_stamp_payment_reminder_sent_at(): void
    {
        [$child, $plan] = $this->seedChildAndPlan();

        $subscription = Subscription::query()->create([
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

    #[Test]
    public function scheduler_does_not_duplicate_queued_payment_reminder(): void
    {
        Queue::fake();

        [$child, $plan] = $this->seedChildAndPlan();

        Subscription::query()->create([
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

        $service = app(NurserySubscriptionService::class);
        $service->sendPaymentReminders((int) $this->tenant->id);
        $service->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertPushed(SendNurseryPaymentReminderWhatsAppJob::class, 1);
    }

    /**
     * @return array{0: Child, 1: SubscriptionPlan}
     */
    private function seedChildAndPlan(): array
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي WhatsApp',
            'phone' => '0501234567',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل WhatsApp',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($plan);

        return [$child, $plan];
    }
}
