<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use App\Models\Nursery\Guardian;
use App\Services\Nursery\Portal\NurseryPortalAuthService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalChildProfileTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    #[Test]
    public function child_profile_shows_medications_read_only(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'ولي',
            'phone' => '0503333333',
        ]);

        $child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'MED-1',
            'name' => 'سالم',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        ChildMedication::query()->create([
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'name' => 'باراسيتامول',
            'dosage' => '5 مل',
            'frequency' => ChildMedication::FREQ_ONCE_DAILY,
            'sort_order' => 0,
        ]);

        app(NurseryPortalAuthService::class)->requestOtp((int) $this->tenant->id, '0503333333');
        app(NurseryPortalAuthService::class)->verifyOtp((int) $this->tenant->id, '0503333333', '123456');

        $this->get(route('nursery.portal.children.show', [
            'tenant_slug' => $this->slug,
            'childId' => $child->id,
        ]))
            ->assertOk()
            ->assertSee('باراسيتامول')
            ->assertSee('عرض للاطلاع فقط')
            ->assertDontSee('name="medications');
    }
}
