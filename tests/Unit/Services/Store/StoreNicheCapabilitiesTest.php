<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Store;

use App\Services\Store\StoreNicheCapabilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StoreNicheCapabilitiesTest extends TestCase
{
    #[Test]
    public function all_core_niches_support_online_store_portal_when_feature_enabled(): void
    {
        $caps = new StoreNicheCapabilities;

        foreach (['retail', 'full_erp', 'manufacturing', 'fleet_agents', 'medical_clinics', 'nurseries'] as $niche) {
            $this->assertTrue($caps->supportsOnlineStorePortal($niche), "Expected portal support for {$niche}");
        }
    }

    #[Test]
    public function only_retail_provisions_online_store_by_default(): void
    {
        $caps = new StoreNicheCapabilities;

        $this->assertTrue($caps->provisionOnlineStoreByDefault('retail'));
        $this->assertFalse($caps->provisionOnlineStoreByDefault('manufacturing'));
        $this->assertFalse($caps->provisionOnlineStoreByDefault('medical_clinics'));
    }

    #[Test]
    public function niche_labels_differ_by_vertical(): void
    {
        $caps = new StoreNicheCapabilities;

        $this->assertSame('معرض منتجات المصنع', $caps->storefrontLabel('manufacturing'));
        $this->assertSame('متجر المستلزمات', $caps->settingsNavLabel('medical_clinics'));
        $this->assertSame('طلبات التحصيل', $caps->ordersNavLabel('fleet_agents'));
    }

    #[Test]
    public function clinic_whatsapp_key_is_allowed_for_clinic_niche(): void
    {
        $caps = new StoreNicheCapabilities;

        $this->assertContains(
            'clinic_whatsapp_automation',
            $caps->whatsappAutomationFeatureKeys('medical_clinics'),
        );
    }
}
