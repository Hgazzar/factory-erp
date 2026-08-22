<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Jobs\Nursery\SendNurserySubscriptionPaidConfirmationWhatsAppJob;
use App\Models\JournalEntry;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionService;
use App\Support\DefaultLedgerAccounts;
use App\Support\PremiumFeatureKeys;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySubscriptionMarkPaidTest extends NurseryTestCase
{
    #[Test]
    public function mark_paid_sets_is_paid_and_status_paid(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();

        $result = app(NurserySubscriptionService::class)->markPaid(
            $subscription,
            (int) $this->tenant->id,
            'cash',
        );

        $subscription->refresh();
        $this->assertTrue($subscription->is_paid);
        $this->assertSame(Subscription::STATUS_PAID, $subscription->status);
        $this->assertNotNull($subscription->paid_at);
        $this->assertSame('cash', $subscription->payment_method);
        $this->assertTrue($result['whatsapp_dispatched']);
        $this->assertFalse($result['already_paid']);
        Queue::assertPushed(SendNurserySubscriptionPaidConfirmationWhatsAppJob::class);
    }

    #[Test]
    public function mark_paid_is_idempotent_and_does_not_duplicate_journal(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();
        $service = app(NurserySubscriptionService::class);

        $first = $service->markPaid($subscription, (int) $this->tenant->id, 'cash');
        $this->assertTrue($first['finance_posted']);
        $this->assertSame('recorded', $first['finance_status']);

        $journals = JournalEntry::query()->where('user_id', $this->tenant->id)->count();

        $second = $service->markPaid($subscription->fresh(), (int) $this->tenant->id, 'cash');
        $this->assertTrue($second['already_paid']);
        $this->assertFalse($second['whatsapp_dispatched']);
        $this->assertSame($journals, JournalEntry::query()->where('user_id', $this->tenant->id)->count());
        Queue::assertPushed(SendNurserySubscriptionPaidConfirmationWhatsAppJob::class, 1);
    }

    #[Test]
    public function finance_disabled_does_not_block_payment(): void
    {
        Queue::fake();

        DB::table('tenant_features')
            ->where('tenant_id', $this->tenant->id)
            ->where('feature_key', PremiumFeatureKeys::NURSERY_SUBSCRIPTION_FINANCE)
            ->delete();
        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $subscription = $this->makeUnpaidSubscription();
        $result = app(NurserySubscriptionService::class)->markPaid(
            $subscription,
            (int) $this->tenant->id,
            'transfer',
        );

        $subscription->refresh();
        $this->assertTrue($subscription->is_paid);
        $this->assertSame(Subscription::STATUS_PAID, $subscription->status);
        $this->assertNull($subscription->journal_entry_id);
        $this->assertFalse($result['finance_posted']);
        $this->assertSame('not_enabled', $result['finance_status']);
        $this->assertTrue($result['whatsapp_dispatched']);
    }

    #[Test]
    public function http_mark_paid_does_not_claim_whatsapp_delivered(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();

        $this->patch(route('nursery.subscriptions.mark-paid', $subscription), [
            'payment_method' => 'cash',
        ])->assertRedirect()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'تم تسجيل الدفع')
                && str_contains($message, 'تمت جدولة')
                && ! str_contains($message, 'تم إرسال تأكيد واتساب'));
    }

    private function ensureLedger(): void
    {
        DefaultLedgerAccounts::salesRevenueForTenant((int) $this->tenant->id);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', (int) $this->tenant->id);
        DefaultLedgerAccounts::vatPayableForTenant((int) $this->tenant->id);
    }

    private function makeUnpaidSubscription(): Subscription
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي دفع',
            'phone' => '0507777888',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل دفع',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->firstOrFail();

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
}
