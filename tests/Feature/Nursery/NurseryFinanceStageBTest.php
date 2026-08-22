<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\Nursery\NurseryFinanceSummaryService;
use App\Services\Nursery\NurserySubscriptionService;
use App\Support\DefaultLedgerAccounts;
use App\Support\NurseryAccess;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryFinanceStageBTest extends NurseryTestCase
{
    #[Test]
    public function mark_paid_cash_transfer_and_card_post_to_correct_asset_accounts(): void
    {
        Queue::fake();
        $this->ensureLedger();

        foreach (['cash' => DefaultLedgerAccounts::CODE_CASH, 'transfer' => DefaultLedgerAccounts::CODE_BANK, 'card' => DefaultLedgerAccounts::CODE_BANK] as $method => $code) {
            $subscription = $this->makeUnpaidSubscription('طفل-'.$method);

            $result = app(NurserySubscriptionService::class)->markPaid(
                $subscription,
                (int) $this->tenant->id,
                $method,
            );

            $this->assertTrue($result['finance_posted'], "Expected finance post for {$method}");
            $subscription->refresh();
            $this->assertSame($method, $subscription->payment_method);
            $this->assertNotNull($subscription->journal_entry_id);

            $asset = DefaultLedgerAccounts::paymentSourceAssetForTenant($method, (int) $this->tenant->id);
            $this->assertSame($code, $asset->code);

            $debit = JournalItem::query()
                ->where('journal_entry_id', $subscription->journal_entry_id)
                ->where('account_id', $asset->id)
                ->where('debit', '>', 0)
                ->exists();
            $this->assertTrue($debit, "Expected debit on {$code} for {$method}");
        }
    }

    #[Test]
    public function mark_paid_does_not_duplicate_journal_entry(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();
        $service = app(NurserySubscriptionService::class);

        $service->markPaid($subscription, (int) $this->tenant->id, 'transfer');
        $count = JournalEntry::query()->where('user_id', $this->tenant->id)->count();

        $second = $service->markPaid($subscription->fresh(), (int) $this->tenant->id, 'card');
        $this->assertTrue($second['already_paid']);
        $this->assertSame($count, JournalEntry::query()->where('user_id', $this->tenant->id)->count());
        $this->assertSame('transfer', $subscription->fresh()->payment_method);
    }

    #[Test]
    public function cancel_reverses_paid_journal_once(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();
        $service = app(NurserySubscriptionService::class);
        $service->markPaid($subscription, (int) $this->tenant->id, 'cash');
        $subscription->refresh();

        $cancelled = $service->cancel($subscription, (int) $this->tenant->id);
        $this->assertNotNull($cancelled->reversal_journal_entry_id);
        $reversalId = $cancelled->reversal_journal_entry_id;

        $service->cancel($cancelled->fresh(), (int) $this->tenant->id);
        $this->assertSame($reversalId, $cancelled->fresh()->reversal_journal_entry_id);
    }

    #[Test]
    public function finance_summary_reports_collected_and_outstanding_amounts(): void
    {
        Queue::fake();
        $this->ensureLedger();

        $paid = $this->makeUnpaidSubscription('مدفوع', 1500);
        app(NurserySubscriptionService::class)->markPaid($paid, (int) $this->tenant->id, 'cash');

        $this->makeUnpaidSubscription('متبقي', 800);

        Payment::withoutGlobalScopes()->create([
            'user_id' => (int) $this->tenant->id,
            'date' => now()->toDateString(),
            'amount' => 200,
            'type' => 'expense',
            'payment_method' => 'cash',
            'created_by' => (int) $this->tenant->id,
        ]);

        $summary = app(NurseryFinanceSummaryService::class)->summarize((int) $this->tenant->id, 'month');

        $this->assertSame(1500.0, $summary['collected_amount']);
        $this->assertSame(1, $summary['collected_count']);
        $this->assertSame(800.0, $summary['outstanding_amount']);
        $this->assertSame(1, $summary['outstanding_count']);
        $this->assertSame(200.0, $summary['expense_amount']);
        $this->assertSame(1300.0, $summary['net_period']);
        $this->assertNotEmpty($summary['by_guardian']);
    }

    #[Test]
    public function owner_can_open_nursery_finance_and_staff_without_permission_cannot(): void
    {
        $this->get(route('nursery.finance.index'))
            ->assertOk()
            ->assertSee('مالية الحضانة', false)
            ->assertSee(route('nursery.finance.index'), false);

        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'nofinance@example.com',
            'password' => 'password',
        ]);

        \App\Models\Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-NF1',
            'name' => 'No Finance',
            'email' => 'nofinance@example.com',
            'status' => 'active',
            'nursery_permissions' => ['login.app', 'subscriptions.manage'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.finance.index'))->assertForbidden();
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('finance.expenses.index'))->assertForbidden();
        $this->assertFalse(app(NurseryAccess::class)->allows(NurseryAccess::CAP_VIEW_FINANCE));
    }

    #[Test]
    public function staff_with_finance_view_can_open_nursery_finance(): void
    {
        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'yesfinance@example.com',
            'password' => 'password',
        ]);

        \App\Models\Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-YF1',
            'name' => 'Yes Finance',
            'email' => 'yesfinance@example.com',
            'status' => 'active',
            'nursery_permissions' => ['login.app', 'finance.view'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.finance.index'))->assertOk();
        $this->assertTrue(app(NurseryAccess::class)->allows(NurseryAccess::CAP_VIEW_FINANCE));
        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('finance.expenses.index'))->assertForbidden();
    }

    #[Test]
    public function tenant_isolation_on_finance_summary(): void
    {
        Queue::fake();
        $other = User::factory()->create(['role' => 'admin']);
        app(\App\Services\Tenant\TenantModuleRegistry::class)->syncModulesForTenant(
            (int) $other->id,
            ['core', 'nursery', 'finance']
        );
        \App\Models\TenantProfile::query()->create([
            'tenant_user_id' => (int) $other->id,
            'niche_key' => 'nurseries',
            'domain' => 'other-nursery',
            'slug' => 'other-nursery',
            'status' => \App\Models\TenantProfile::STATUS_ACTIVE,
        ]);

        $this->makeUnpaidSubscription('طفل أ', 900);

        $this->actingAs($other);
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $other->id);
        $guardian = Guardian::query()->create([
            'user_id' => (int) $other->id,
            'name' => 'ولي ب',
            'phone' => '0501111222',
        ]);
        $child = Child::query()->create([
            'user_id' => (int) $other->id,
            'name' => 'طفل ب',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
        $plan = SubscriptionPlan::query()->where('user_id', $other->id)->firstOrFail();
        Subscription::query()->create([
            'user_id' => (int) $other->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'amount_after_tax' => 300,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_UNPAID,
        ]);

        $summaryA = app(NurseryFinanceSummaryService::class)->summarize((int) $this->tenant->id, 'all');
        $summaryB = app(NurseryFinanceSummaryService::class)->summarize((int) $other->id, 'all');

        $this->assertSame(900.0, $summaryA['outstanding_amount']);
        $this->assertSame(300.0, $summaryB['outstanding_amount']);
    }

    #[Test]
    public function http_mark_paid_accepts_transfer_method(): void
    {
        Queue::fake();
        $this->ensureLedger();
        $subscription = $this->makeUnpaidSubscription();

        $this->patch(route('nursery.subscriptions.mark-paid', $subscription), [
            'payment_method' => 'transfer',
        ])->assertRedirect();

        $subscription->refresh();
        $this->assertSame('transfer', $subscription->payment_method);
        $this->assertTrue($subscription->is_paid);
        $this->assertNotNull($subscription->journal_entry_id);
    }

    #[Test]
    public function sidebar_shows_finance_for_owner_not_for_plain_staff(): void
    {
        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee(route('nursery.finance.index'), false);

        $staff = User::factory()->create([
            'role' => 'supervisor',
            'email' => 'sidebarnofin@example.com',
            'password' => 'password',
        ]);

        \App\Models\Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $staff->id,
            'code' => 'EMP-SNF',
            'name' => 'Sidebar Staff',
            'email' => 'sidebarnofin@example.com',
            'status' => 'active',
            'nursery_permissions' => ['login.app'],
        ]);

        $this->actingAs($staff);

        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertDontSee(route('nursery.finance.index'), false);
    }

    private function ensureLedger(): void
    {
        DefaultLedgerAccounts::salesRevenueForTenant((int) $this->tenant->id);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', (int) $this->tenant->id);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('transfer', (int) $this->tenant->id);
        DefaultLedgerAccounts::vatPayableForTenant((int) $this->tenant->id);
    }

    private function makeUnpaidSubscription(string $childName = 'طفل مالي', float $amount = 2000): Subscription
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي '.$childName,
            'phone' => '05'.str_pad((string) random_int(10000000, 99999999), 8, '0'),
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => $childName,
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
            'amount_after_tax' => $amount,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_UNPAID,
        ]);
    }
}
