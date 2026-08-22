<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryGuardianAdminTest extends NurseryTestCase
{
    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'أم نورة',
            'phone' => '0559998888',
            'email' => 'parent@example.test',
        ]);

        $this->child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'guardian_id' => $this->guardian->id,
            'name' => 'نورة',
            'status' => Child::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function guardians_index_lists_guardians_with_filters(): void
    {
        $this->actingAs($this->tenant)
            ->get(route('nursery.guardians.index'))
            ->assertOk()
            ->assertSee('أم نورة')
            ->assertSee('0559998888')
            ->assertSee('parent@example.test');

        $this->actingAs($this->tenant)
            ->get(route('nursery.guardians.index', ['q' => '055999']))
            ->assertOk()
            ->assertSee('أم نورة');

        $this->actingAs($this->tenant)
            ->get(route('nursery.guardians.index', ['q' => 'غير موجود']))
            ->assertOk()
            ->assertDontSee('أم نورة');
    }

    #[Test]
    public function guardian_show_lists_linked_children(): void
    {
        $this->actingAs($this->tenant)
            ->get(route('nursery.guardians.show', $this->guardian))
            ->assertOk()
            ->assertSee('أم نورة')
            ->assertSee('نورة');
    }

    #[Test]
    public function admin_can_resend_invite_and_revoke_portal_access(): void
    {
        $this->actingAs($this->tenant)
            ->post(route('nursery.guardians.portal-invite', $this->guardian))
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'تمت جدولة')
                && ! str_contains($message, 'وهمي'));

        $this->guardian->refresh();
        $this->assertNotEmpty($this->guardian->portal_access_token);

        $this->actingAs($this->tenant)
            ->delete(route('nursery.guardians.revoke-portal', $this->guardian))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->guardian->refresh();
        $this->assertNull($this->guardian->portal_access_token);
    }
}
