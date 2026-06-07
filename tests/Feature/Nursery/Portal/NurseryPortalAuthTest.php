<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use App\Models\Nursery\Guardian;
use App\Models\User;
use App\Support\PremiumFeatureKeys;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryPortalAuthTest extends NurseryTestCase
{
    private string $slug = 'test-nursery';

    private Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guardian = Guardian::query()->create([
            'user_id' => $this->tenant->id,
            'name' => 'أم سارة',
            'phone' => '0551234567',
        ]);
    }

    #[Test]
    public function portal_login_page_is_public_when_feature_enabled(): void
    {
        $this->get(route('nursery.portal.login', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('مرحباً بك')
            ->assertSee('123456');
    }

    #[Test]
    public function portal_returns_404_without_premium_feature(): void
    {
        DB::table('tenant_features')
            ->where('tenant_id', $this->tenant->id)
            ->where('feature_key', PremiumFeatureKeys::NURSERY_PARENT_PORTAL)
            ->delete();

        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $this->get(route('nursery.portal.login', ['tenant_slug' => $this->slug]))
            ->assertNotFound();
    }

    #[Test]
    public function guardian_can_login_with_otp_and_see_home(): void
    {
        $this->post(route('nursery.portal.otp.request', ['tenant_slug' => $this->slug]), [
            'phone' => '0551234567',
        ])->assertRedirect();

        $this->post(route('nursery.portal.otp.verify', ['tenant_slug' => $this->slug]), [
            'phone' => '0551234567',
            'otp' => '123456',
        ])->assertRedirect(route('nursery.portal.home', ['tenant_slug' => $this->slug]));

        Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'C-001',
            'name' => 'سارة',
            'guardian_id' => $this->guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->get(route('nursery.portal.home', ['tenant_slug' => $this->slug]))
            ->assertOk()
            ->assertSee('أم سارة')
            ->assertSee('سارة');
    }

    #[Test]
    public function home_requires_authentication(): void
    {
        $this->get(route('nursery.portal.home', ['tenant_slug' => $this->slug]))
            ->assertRedirect(route('nursery.portal.login', ['tenant_slug' => $this->slug]));
    }

    #[Test]
    public function invite_token_logs_guardian_in(): void
    {
        $token = str_repeat('a', 48);
        $this->guardian->forceFill([
            'portal_access_token' => $token,
            'portal_invited_at' => now(),
        ])->save();

        $this->get(route('nursery.portal.invite', ['tenant_slug' => $this->slug, 'token' => $token]))
            ->assertRedirect(route('nursery.portal.home', ['tenant_slug' => $this->slug]));

        $this->get(route('nursery.portal.home', ['tenant_slug' => $this->slug]))
            ->assertOk();
    }

    #[Test]
    public function wrong_niche_slug_returns_404(): void
    {
        $otherTenant = User::factory()->create(['role' => 'admin']);

        \App\Models\TenantProfile::query()->create([
            'tenant_user_id' => (int) $otherTenant->id,
            'niche_key' => 'medical_clinics',
            'domain' => 'clinic-only',
            'slug' => 'clinic-only',
            'status' => \App\Models\TenantProfile::STATUS_ACTIVE,
        ]);

        DB::table('tenant_features')->insert([
            'tenant_id' => $otherTenant->id,
            'feature_key' => PremiumFeatureKeys::NURSERY_PARENT_PORTAL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('nursery.portal.login', ['tenant_slug' => 'clinic-only']))
            ->assertNotFound();
    }

    #[Test]
    public function admin_child_show_uses_shared_medications_partial(): void
    {
        $child = Child::query()->create([
            'user_id' => $this->tenant->id,
            'code' => 'C-002',
            'name' => 'ليان',
            'guardian_id' => $this->guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        ChildMedication::query()->create([
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'name' => 'باراسيتامول',
            'dosage' => '5 مل',
            'frequency' => ChildMedication::FREQ_TWICE_DAILY,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->tenant)
            ->get(route('nursery.children.show', $child))
            ->assertOk()
            ->assertSee('باراسيتامول')
            ->assertSee('مرتين يومياً');
    }
}
