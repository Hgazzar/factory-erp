<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
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
            ->assertSee('التذكيرات');

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($plan);

        $this->post(route('nursery.subscriptions.store'), [
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
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
}
