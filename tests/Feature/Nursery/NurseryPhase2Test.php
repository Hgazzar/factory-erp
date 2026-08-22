<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryShift;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionService;
use App\Support\DefaultLedgerAccounts;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPhase2Test extends NurseryTestCase
{
    #[Test]
    public function paid_subscription_can_post_finance_journal(): void
    {
        DefaultLedgerAccounts::salesRevenueForTenant((int) $this->tenant->id);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', (int) $this->tenant->id);
        DefaultLedgerAccounts::vatPayableForTenant((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي',
            'phone' => '0501112233',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'سارة',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $service = app(NurserySubscriptionService::class);
        $service->ensureDefaultPlans((int) $this->tenant->id);

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($plan);

        $result = $service->create((int) $this->tenant->id, [
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'is_paid' => true,
        ]);

        $sub = $result['subscription'];
        $this->assertTrue($result['finance_posted']);
        $this->assertNotNull($sub->journal_entry_id);
        $this->assertNotNull($sub->paid_at);
    }

    #[Test]
    public function dashboard_shows_subscription_kpis(): void
    {
        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee('الاشتراكات');
    }

    #[Test]
    public function payment_reminders_endpoint_runs_without_error(): void
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);
        $planId = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->value('id');

        Subscription::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => Child::query()->create([
                'user_id' => (int) $this->tenant->id,
                'name' => 'طفل',
                'guardian_id' => Guardian::query()->create([
                    'user_id' => (int) $this->tenant->id,
                    'name' => 'ولي',
                    'phone' => '0550000000',
                ])->id,
                'status' => Child::STATUS_ACTIVE,
            ])->id,
            'plan_id' => $planId,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
            'amount_after_tax' => 100,
            'is_paid' => false,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->post(route('nursery.subscriptions.reminders.payment'))
            ->assertRedirect();
    }

    #[Test]
    public function staff_can_be_assigned_nursery_shift(): void
    {
        $shift = NurseryShift::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'صباحي',
            'start_time' => '07:00:00',
            'end_time' => '14:00:00',
            'is_active' => true,
        ]);

        $response = $this->post(route('nursery.staff.store'), [
            'first_name' => 'نورة',
            'last_name' => 'أحمد',
            'email' => 'nora@example.com',
            'mobile' => '0551234567',
            'nursery_shift_id' => $shift->id,
        ]);

        $response->assertRedirect(route('nursery.staff.index'));

        $this->assertDatabaseHas('employees', [
            'user_id' => $this->tenant->id,
            'nursery_shift_id' => $shift->id,
        ]);
    }
}
