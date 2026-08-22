<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Models\User;
use App\Services\Nursery\NurserySubscriptionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySubscriptionPermissionTest extends NurseryTestCase
{
    #[Test]
    public function view_only_staff_can_open_subscriptions_but_cannot_manage(): void
    {
        $staff = $this->makeLinkedStaff('sub-view@example.com', ['login.app']);
        $subscription = $this->makeUnpaidSubscription();

        $this->actingAs($staff);

        $this->get(route('nursery.subscriptions.index'))
            ->assertOk()
            ->assertDontSee('+ إضافة اشتراك')
            ->assertDontSee('تسجيل الدفع');

        $this->post(route('nursery.subscriptions.store'), [
            'child_id' => $subscription->child_id,
            'plan_id' => $subscription->plan_id,
            'starts_on' => now()->format('Y-m-d'),
            'ends_on' => now()->addMonth()->format('Y-m-d'),
        ])->assertForbidden();

        $this->patch(route('nursery.subscriptions.mark-paid', $subscription), [
            'payment_method' => 'cash',
        ])->assertForbidden();

        $this->patch(route('nursery.subscriptions.cancel', $subscription))->assertForbidden();
        $this->post(route('nursery.subscriptions.renew', $subscription))->assertForbidden();
    }

    #[Test]
    public function manage_subscriptions_staff_can_mark_paid_and_renew(): void
    {
        $staff = $this->makeLinkedStaff('sub-mgr@example.com', ['login.app', 'subscriptions.manage']);
        $subscription = $this->makeUnpaidSubscription();

        $this->actingAs($staff);

        $this->get(route('nursery.subscriptions.index'))
            ->assertOk()
            ->assertSee('+ إضافة اشتراك');

        $this->patch(route('nursery.subscriptions.mark-paid', $subscription), [
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->assertSame(Subscription::STATUS_PAID, $subscription->fresh()->status);

        $this->post(route('nursery.subscriptions.renew', $subscription->fresh()))
            ->assertRedirect(route('nursery.subscriptions.index'));

        $this->assertDatabaseHas('nursery_subscriptions', [
            'renewed_from_id' => $subscription->id,
            'status' => Subscription::STATUS_UNPAID,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeLinkedStaff(string $email, array $permissions): User
    {
        $user = User::factory()->create([
            'role' => 'supervisor',
            'email' => $email,
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $user->id,
            'code' => 'EMP-'.substr(md5($email), 0, 6),
            'name' => 'Staff '.$email,
            'email' => $email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $user;
    }

    private function makeUnpaidSubscription(): Subscription
    {
        app(NurserySubscriptionService::class)->ensureDefaultPlans((int) $this->tenant->id);

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي صلاحيات',
            'phone' => '0504444555',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'طفل صلاحيات',
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
