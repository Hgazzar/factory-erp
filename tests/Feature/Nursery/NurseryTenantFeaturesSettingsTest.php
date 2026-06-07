<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Support\PremiumFeatureKeys;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryTenantFeaturesSettingsTest extends NurseryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    #[Test]
    public function settings_features_tab_can_toggle_portal(): void
    {
        $this->get(route('nursery.settings.index', ['tab' => 'features']))
            ->assertOk()
            ->assertSee('مزايا الحضانة')
            ->assertSee('بوابة أولياء الأمور');

        DB::table('tenant_features')
            ->where('tenant_id', $this->tenant->id)
            ->where('feature_key', PremiumFeatureKeys::NURSERY_PORTAL)
            ->delete();
        app(\App\Services\Tenant\TenantFeatureRegistry::class)->forgetCache((int) $this->tenant->id);

        $this->assertFalse($this->tenant->fresh()->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL));

        $this->put(route('nursery.settings.features.update'), [
            'features' => [PremiumFeatureKeys::NURSERY_PORTAL],
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'features']));

        $this->assertTrue($this->tenant->fresh()->hasFeature(PremiumFeatureKeys::NURSERY_PORTAL));
    }
}
