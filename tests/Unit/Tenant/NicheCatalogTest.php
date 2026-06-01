<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Services\Tenant\NicheCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NicheCatalogTest extends TestCase
{
    #[Test]
    public function all_six_niches_are_defined(): void
    {
        $catalog = app(NicheCatalog::class);

        $this->assertCount(6, $catalog->keys());
        $this->assertSame([
            'manufacturing',
            'retail',
            'medical_clinics',
            'nurseries',
            'fleet_agents',
            'full_erp',
        ], $catalog->keys());
    }

    #[Test]
    public function manufacturing_default_modules_exclude_pos(): void
    {
        $modules = app(NicheCatalog::class)->defaultModuleKeys('manufacturing');

        $this->assertContains('manufacturing', $modules);
        $this->assertContains('inventory', $modules);
        $this->assertNotContains('pos', $modules);
    }

    #[Test]
    public function retail_default_modules_include_pos_and_crm(): void
    {
        $modules = app(NicheCatalog::class)->defaultModuleKeys('retail');

        $this->assertContains('pos', $modules);
        $this->assertContains('crm', $modules);
        $this->assertNotContains('manufacturing', $modules);
    }

    #[Test]
    public function lexicon_overrides_exist_for_each_niche(): void
    {
        foreach (app(NicheCatalog::class)->keys() as $nicheKey) {
            $overrides = config("lexicon.niche_overrides.{$nicheKey}");

            $this->assertIsArray($overrides, "Missing lexicon overrides for {$nicheKey}");
            $this->assertNotEmpty($overrides, "Empty lexicon overrides for {$nicheKey}");
        }
    }

    #[Test]
    public function manufacturing_lexicon_renames_inventory(): void
    {
        $this->assertSame(
            'مخزن الخامات',
            config('lexicon.niche_overrides.manufacturing')['modules.inventory']
        );

        $this->assertSame(
            'المعرض والمستودع',
            config('lexicon.niche_overrides.retail')['modules.inventory']
        );

        $this->assertSame(
            'العهدة',
            config('lexicon.niche_overrides.fleet_agents')['modules.inventory']
        );
    }

    #[Test]
    public function medical_clinics_default_modules_include_clinic(): void
    {
        $modules = app(NicheCatalog::class)->defaultModuleKeys('medical_clinics');

        $this->assertContains('clinic', $modules);
    }
}
