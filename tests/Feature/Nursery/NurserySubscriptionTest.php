<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\NurserySubscriptionPlanService;
use App\Services\Nursery\NurserySubscriptionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySubscriptionTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_view_and_create_subscription(): void
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي',
            'phone' => '0501234567',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'فهد',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->get(route('nursery.subscriptions.index'))
            ->assertOk()
            ->assertSee('إجمالي الاشتراكات')
            ->assertSee('المنتهية')
            ->assertSee('التذكيرات')
            ->assertSeeInOrder(['الكل', 'ساعة', 'يومي', 'أسبوعي', 'فصلي']);

        $plan = SubscriptionPlan::query()
            ->where('user_id', $this->tenant->id)
            ->where('plan_type', 'weekly')
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($plan);

        $this->post(route('nursery.subscriptions.store'), [
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addWeek()->format('Y-m-d'),
            'is_paid' => '1',
        ])->assertRedirect(route('nursery.subscriptions.index'));

        $this->assertDatabaseHas('nursery_subscriptions', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'is_paid' => true,
            'status' => Subscription::STATUS_PAID,
        ]);

        $sub = Subscription::query()->where('child_id', $child->id)->first();
        $this->patch(route('nursery.subscriptions.cancel', $sub))
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame(Subscription::STATUS_CANCELLED, $sub->status);
    }

    #[Test]
    public function default_plans_cover_period_types_without_yearly(): void
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $activeTypes = SubscriptionPlan::query()
            ->where('user_id', $this->tenant->id)
            ->where('is_active', true)
            ->pluck('plan_type')
            ->all();

        $this->assertEqualsCanonicalizing(
            ['hourly', 'daily', 'weekly', 'term'],
            $activeTypes
        );
        $this->assertNotContains('yearly', $activeTypes);
        $this->assertNotContains('monthly', $activeTypes);

        $options = app(NurserySubscriptionPlanService::class)->planTypeOptions();
        $this->assertArrayHasKey('hourly', $options);
        $this->assertArrayHasKey('daily', $options);
        $this->assertArrayHasKey('weekly', $options);
        $this->assertArrayHasKey('term', $options);
        $this->assertArrayNotHasKey('yearly', $options);
        $this->assertArrayNotHasKey('monthly', $options);
    }

    #[Test]
    public function weekly_subscription_auto_fills_end_date_from_start(): void
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي تواريخ',
            'phone' => '0507654321',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'سارة',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);
        $plan = SubscriptionPlan::query()
            ->where('user_id', $this->tenant->id)
            ->where('plan_type', 'weekly')
            ->where('is_active', true)
            ->firstOrFail();

        $starts = now()->format('Y-m-d');
        $expectedEnd = now()->addWeek()->format('Y-m-d');

        $this->post(route('nursery.subscriptions.store'), [
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => $starts,
            'is_paid' => '0',
        ])->assertRedirect(route('nursery.subscriptions.index'));

        $sub = Subscription::query()->where('child_id', $child->id)->firstOrFail();
        $this->assertSame($starts, $sub->starts_on?->toDateString());
        $this->assertSame($expectedEnd, $sub->ends_on?->toDateString());
    }

    #[Test]
    public function subscription_start_date_cannot_be_in_the_past(): void
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي ماضي',
            'phone' => '0501112233',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'نورة',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);
        $plan = SubscriptionPlan::query()
            ->where('user_id', $this->tenant->id)
            ->where('plan_type', 'daily')
            ->where('is_active', true)
            ->firstOrFail();

        $this->from(route('nursery.subscriptions.index'))
            ->post(route('nursery.subscriptions.store'), [
                'child_id' => $child->id,
                'plan_id' => $plan->id,
                'starts_on' => now()->subDay()->format('Y-m-d'),
                'ends_on' => now()->format('Y-m-d'),
                'is_paid' => '0',
            ])
            ->assertRedirect(route('nursery.subscriptions.index'))
            ->assertSessionHasErrors('starts_on');

        $this->assertDatabaseMissing('nursery_subscriptions', [
            'child_id' => $child->id,
        ]);
    }

    #[Test]
    public function weekly_and_term_auto_end_dates_follow_plan_type(): void
    {
        $service = app(NurserySubscriptionPlanService::class);

        $this->assertSame(
            '2025-09-26',
            $service->endsOnForPlanType('2025-09-19', 'weekly')?->toDateString()
        );
        $this->assertSame(
            '2025-12-19',
            $service->endsOnForPlanType('2025-09-19', 'term')?->toDateString()
        );
        $this->assertSame(
            '2025-09-19',
            $service->endsOnForPlanType('2025-09-19', 'hourly')?->toDateString()
        );
        $this->assertNull($service->endsOnForPlanType('2025-09-19', 'custom'));
    }

    #[Test]
    public function plans_are_sorted_hour_daily_weekly_term(): void
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $names = app(NurserySubscriptionPlanService::class)
            ->listForTenant((int) $this->tenant->id)
            ->where('is_active', true)
            ->pluck('name')
            ->values()
            ->all();

        $this->assertSame(['ساعة', 'يومي', 'أسبوعي', 'فصلي'], $names);
    }
}
