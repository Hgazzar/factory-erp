<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Jobs\Nursery\SendNurseryPaymentReminderWhatsAppJob;
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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySubscriptionLifecycleTest extends NurseryTestCase
{
    #[Test]
    public function expire_command_expires_unpaid_past_ends_on_and_leaves_paid(): void
    {
        Queue::fake();
        [$unpaid, $paid] = $this->seedUnpaidAndPaidPastEnd();

        $this->artisan('nursery:expire-subscriptions')
            ->assertSuccessful();

        $this->assertSame(Subscription::STATUS_EXPIRED, $unpaid->fresh()->status);
        $this->assertSame(Subscription::STATUS_PAID, $paid->fresh()->status);
        $this->assertTrue($paid->fresh()->is_paid);
    }

    #[Test]
    public function cancel_with_journal_creates_reversal_once(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();

        app(NurserySubscriptionService::class)->markPaid(
            $subscription,
            (int) $this->tenant->id,
            'cash',
        );

        $subscription->refresh();
        $this->assertNotNull($subscription->journal_entry_id);
        $journalsAfterPay = JournalEntry::query()->where('user_id', $this->tenant->id)->count();

        $cancelled = app(NurserySubscriptionService::class)->cancel(
            $subscription,
            (int) $this->tenant->id,
        );

        $this->assertSame(Subscription::STATUS_CANCELLED, $cancelled->status);
        $this->assertTrue($cancelled->is_paid);
        $this->assertNotNull($cancelled->reversal_journal_entry_id);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $cancelled->reversal_journal_entry_id,
            'reference' => 'NUR-SUB-'.$cancelled->id.'-REV',
        ]);
        $this->assertSame($journalsAfterPay + 1, JournalEntry::query()->where('user_id', $this->tenant->id)->count());

        $reversalId = $cancelled->reversal_journal_entry_id;
        app(NurserySubscriptionService::class)->cancel($cancelled->fresh(), (int) $this->tenant->id);

        $this->assertSame($reversalId, $cancelled->fresh()->reversal_journal_entry_id);
        $this->assertSame($journalsAfterPay + 1, JournalEntry::query()->where('user_id', $this->tenant->id)->count());
    }

    #[Test]
    public function expire_is_idempotent_and_skips_cancelled_and_creates_no_journal(): void
    {
        Queue::fake();
        $this->ensureLedger();
        [$unpaid, $paid] = $this->seedUnpaidAndPaidPastEnd();

        $cancelledPast = Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $unpaid->child_id,
            'plan_id' => $unpaid->plan_id,
            'starts_on' => now()->subMonths(2)->format('Y-m-d'),
            'ends_on' => now()->subDay()->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_CANCELLED,
        ]);

        $journalsBefore = JournalEntry::query()->where('user_id', $this->tenant->id)->count();

        $this->artisan('nursery:expire-subscriptions')->assertSuccessful();
        $this->artisan('nursery:expire-subscriptions')->assertSuccessful();

        $this->assertSame(Subscription::STATUS_EXPIRED, $unpaid->fresh()->status);
        $this->assertSame(Subscription::STATUS_PAID, $paid->fresh()->status);
        $this->assertSame(Subscription::STATUS_CANCELLED, $cancelledPast->fresh()->status);
        $this->assertSame($journalsBefore, JournalEntry::query()->where('user_id', $this->tenant->id)->count());
    }

    #[Test]
    public function expired_subscription_is_excluded_from_payment_reminders(): void
    {
        Queue::fake();
        [$unpaid] = $this->seedUnpaidAndPaidPastEnd();
        $unpaid->forceFill(['status' => Subscription::STATUS_EXPIRED])->save();

        app(NurserySubscriptionService::class)->sendPaymentReminders((int) $this->tenant->id);

        Queue::assertNothingPushed();
        Queue::assertNotPushed(SendNurseryPaymentReminderWhatsAppJob::class);
    }

    #[Test]
    public function create_unpaid_starts_unpaid_and_not_paid(): void
    {
        Queue::fake();
        $subscription = $this->makeUnpaidSubscription();

        $this->assertFalse($subscription->is_paid);
        $this->assertSame(Subscription::STATUS_UNPAID, $subscription->status);
        $this->assertNull($subscription->journal_entry_id);
    }

    #[Test]
    public function renew_creates_new_row_with_renewed_from_id_and_does_not_mutate_old(): void
    {
        Queue::fake();
        $old = $this->makeUnpaidSubscription();
        $old->forceFill([
            'is_paid' => true,
            'status' => Subscription::STATUS_PAID,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ])->save();
        $oldSnapshot = $old->only(['starts_on', 'ends_on', 'status', 'is_paid', 'amount_after_tax']);

        $renewed = app(NurserySubscriptionService::class)->renew($old, (int) $this->tenant->id, (int) $this->tenant->id);

        $old->refresh();
        $this->assertSame($oldSnapshot['status'], $old->status);
        $this->assertTrue($old->is_paid);
        $this->assertSame($oldSnapshot['starts_on']->toDateString(), $old->starts_on->toDateString());
        $this->assertSame($oldSnapshot['ends_on']->toDateString(), $old->ends_on->toDateString());
        $this->assertNotSame($old->id, $renewed->id);
        $this->assertSame($old->id, (int) $renewed->renewed_from_id);
        $this->assertFalse($renewed->is_paid);
        $this->assertSame(Subscription::STATUS_UNPAID, $renewed->status);
        $this->assertSame($old->ends_on->copy()->addDay()->toDateString(), $renewed->starts_on->toDateString());

        $again = app(NurserySubscriptionService::class)->renew($old->fresh(), (int) $this->tenant->id);
        $this->assertSame($renewed->id, $again->id);
        $this->assertSame(2, Subscription::query()->where('child_id', $old->child_id)->count());
    }

    #[Test]
    public function cannot_renew_cancelled_subscription(): void
    {
        Queue::fake();
        $subscription = $this->makeUnpaidSubscription();
        app(NurserySubscriptionService::class)->cancel($subscription, (int) $this->tenant->id);

        $this->expectException(InvalidArgumentException::class);
        app(NurserySubscriptionService::class)->renew($subscription->fresh(), (int) $this->tenant->id);
    }

    #[Test]
    public function cancel_still_succeeds_when_finance_is_off(): void
    {
        Queue::fake();

        DB::table('tenant_features')
            ->where('tenant_id', $this->tenant->id)
            ->where('feature_key', PremiumFeatureKeys::NURSERY_SUBSCRIPTION_FINANCE)
            ->delete();
        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $subscription = $this->makeUnpaidSubscription();
        $subscription->forceFill([
            'is_paid' => true,
            'status' => Subscription::STATUS_PAID,
            'journal_entry_id' => null,
        ])->save();

        $cancelled = app(NurserySubscriptionService::class)->cancel(
            $subscription,
            (int) $this->tenant->id,
        );

        $this->assertSame(Subscription::STATUS_CANCELLED, $cancelled->status);
        $this->assertNull($cancelled->reversal_journal_entry_id);
    }

    /**
     * @return array{0: Subscription, 1: Subscription}
     */
    private function seedUnpaidAndPaidPastEnd(): array
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي دورة حياة',
            'phone' => '0501111222',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل دورة حياة',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->firstOrFail();

        $unpaid = Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->subMonths(2)->format('Y-m-d'),
            'ends_on' => now()->subDay()->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_UNPAID,
        ]);

        $paid = Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->subMonths(2)->format('Y-m-d'),
            'ends_on' => now()->subDay()->format('Y-m-d'),
            'amount_after_tax' => 2000,
            'discount_amount' => 0,
            'is_paid' => true,
            'status' => Subscription::STATUS_PAID,
        ]);

        return [$unpaid, $paid];
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
            'name' => 'ولي إلغاء',
            'phone' => '0503333444',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل إلغاء',
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
