<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildDailyActivity;
use App\Models\Nursery\Guardian;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use App\Support\NurseryChildDailyActivityCatalog;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalDailyActivityTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي يوم',
            'phone' => '0507777888',
        ]);

        $this->child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'DAY-1',
            'name' => 'جنى',
            'guardian_id' => $this->guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function parent_sees_visible_activities_and_not_internal_notes(): void
    {
        ChildDailyActivity::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $this->child->id,
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_MEAL,
            'payload' => ['meal' => 'breakfast', 'amount' => 'eaten'],
            'is_parent_visible' => true,
            'recorded_by' => (int) $this->tenant->id,
            'recorded_at' => now(),
        ]);

        ChildDailyActivity::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $this->child->id,
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_NOTE,
            'payload' => [],
            'note' => 'ملاحظة داخلية للطاقم',
            'is_parent_visible' => false,
            'recorded_by' => (int) $this->tenant->id,
            'recorded_at' => now(),
        ]);

        $this->loginPortal('0507777888');

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $this->child->id,
        ]))
            ->assertOk()
            ->assertSee('يوم الطفل')
            ->assertSee('فطور')
            ->assertSee('أكل كامل')
            ->assertDontSee('ملاحظة داخلية للطاقم')
            ->assertDontSee('حفظ الوجبة')
            ->assertDontSee('إظهار لولي الأمر');
    }

    #[Test]
    public function parent_cannot_see_another_childs_activities(): void
    {
        $otherGuardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي آخر',
            'phone' => '0507777999',
        ]);
        $otherChild = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'DAY-2',
            'name' => 'طارق',
            'guardian_id' => $otherGuardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        ChildDailyActivity::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $otherChild->id,
            'activity_date' => now()->toDateString(),
            'type' => NurseryChildDailyActivityCatalog::TYPE_ACTIVITY,
            'payload' => ['title' => 'نشاط طارق السري'],
            'is_parent_visible' => true,
            'recorded_by' => (int) $this->tenant->id,
            'recorded_at' => now(),
        ]);

        $this->loginPortal('0507777888');

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $otherChild->id,
        ]))->assertNotFound();

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $this->child->id,
        ]))
            ->assertOk()
            ->assertDontSee('نشاط طارق السري');
    }

    #[Test]
    public function invalid_guardian_session_cannot_open_child_profile(): void
    {
        $this->loginPortal('0507777888');

        Session::put('nursery_portal.session', [
            'tenant_user_id' => (int) $this->tenant->id,
            'guardian_id' => 999999,
        ]);

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $this->child->id,
        ]))->assertRedirect(route('nursery.portal.login', ['tenant_slug' => $this->slug]));
    }

    private function loginPortal(string $phone): void
    {
        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, $phone);
        app(NurseryPortalAuthService::class)->verifyOtp((int) $this->tenant->id, $phone, '123456');
    }
}
