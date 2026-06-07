<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalFinanceTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    #[Test]
    public function finance_page_shows_guardian_children_subscriptions_only(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي مالي',
            'phone' => '0504444444',
        ]);

        $otherGuardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي آخر',
            'phone' => '0505555555',
        ]);

        $child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'FIN-1',
            'name' => 'ليان',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $otherChild = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'FIN-2',
            'name' => 'طفل آخر',
            'guardian_id' => $otherGuardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $plan = SubscriptionPlan::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'شهري',
            'plan_type' => 'monthly',
            'amount' => 1000,
            'tax_rate' => 15,
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'amount_after_tax' => 1150,
            'discount_amount' => 0,
            'is_paid' => true,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Subscription::query()->create([
            'user_id' => $this->tenant->id,
            'child_id' => $otherChild->id,
            'plan_id' => $plan->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonth()->toDateString(),
            'amount_after_tax' => 1150,
            'discount_amount' => 0,
            'is_paid' => false,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, '0504444444');
        app(NurseryPortalAuthService::class)->verifyOtp((int) $this->tenant->id, '0504444444', '123456');

        $this->get(route('nursery.portal.finance', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('ليان')
            ->assertSee('مدفوع')
            ->assertDontSee('طفل آخر');
    }
}
