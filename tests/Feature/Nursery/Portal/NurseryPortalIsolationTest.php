<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalIsolationTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    private Guardian $guardianA;

    private Child $childA;

    private Child $childB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guardianA = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي أ',
            'phone' => '0501111111',
        ]);

        $guardianB = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي ب',
            'phone' => '0502222222',
        ]);

        $this->childA = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'ISO-A',
            'name' => 'طفل أ',
            'guardian_id' => $this->guardianA->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->childB = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'ISO-B',
            'name' => 'طفل ب',
            'guardian_id' => $guardianB->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function guardian_cannot_view_another_guardians_child_profile(): void
    {
        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, '0501111111');
        app(NurseryPortalAuthService::class)->verifyOtp(
            (int) $this->tenant->id,
            '0501111111',
            '123456',
        );

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $this->childB->id,
        ]))->assertNotFound();
    }

    #[Test]
    public function guardian_can_view_own_child_profile(): void
    {
        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, '0501111111');
        app(NurseryPortalAuthService::class)->verifyOtp((int) $this->tenant->id, '0501111111', '123456');

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $this->childA->id,
        ]))
            ->assertOk()
            ->assertSee('طفل أ');
    }
}
