<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Store;

use App\Support\CheckoutBoundaryCatalog;
use App\Support\StoreFeatureKeys;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StorePremiumFeaturesCatalogTest extends TestCase
{
    #[Test]
    public function every_store_enabled_niche_has_online_store_in_premium_catalog(): void
    {
        $catalog = config('premium_features.niches', []);

        foreach (CheckoutBoundaryCatalog::premiumCatalogNicheKeys() as $nicheKey) {
            $features = collect($catalog[$nicheKey] ?? [])
                ->pluck('key')
                ->all();

            $this->assertContains(
                StoreFeatureKeys::ONLINE_STORE,
                $features,
                "Missing online_store premium feature for niche {$nicheKey}",
            );
        }
    }

    #[Test]
    public function full_erp_inherits_online_store_from_listed_verticals(): void
    {
        $catalog = config('premium_features.niches', []);
        $keys = config('premium_features.full_erp_niche_keys', []);

        $this->assertNotEmpty($keys);

        foreach ($keys as $nicheKey) {
            $features = collect($catalog[$nicheKey] ?? [])
                ->pluck('key')
                ->all();

            $this->assertContains(
                StoreFeatureKeys::ONLINE_STORE,
                $features,
                "full_erp aggregate missing online_store on {$nicheKey}",
            );
        }
    }

    #[Test]
    public function online_store_premium_entries_require_pos_module(): void
    {
        $catalog = config('premium_features.niches', []);

        foreach (CheckoutBoundaryCatalog::premiumCatalogNicheKeys() as $nicheKey) {
            $onlineStore = collect($catalog[$nicheKey] ?? [])
                ->firstWhere('key', StoreFeatureKeys::ONLINE_STORE);

            $this->assertIsArray($onlineStore);
            $this->assertSame('pos', $onlineStore['requires_module'] ?? null);
        }
    }
}
