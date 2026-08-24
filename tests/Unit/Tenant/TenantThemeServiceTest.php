<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Services\Tenant\TenantThemeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantThemeServiceTest extends TestCase
{
    #[Test]
    public function it_builds_css_variables_only_for_requested_module_prefixes(): void
    {
        $clinicVars = app(TenantThemeService::class)->cssVariables(
            '#0d9488',
            '#ccfbf1',
            null,
            null,
            ['clinic', 'cp'],
        );
        $nurseryVars = app(TenantThemeService::class)->cssVariables(
            '#0F766E',
            '#F0FDFA',
            null,
            null,
            ['nursery', 'np'],
        );

        $this->assertSame('#0d9488', $clinicVars['--clinic-primary']);
        $this->assertSame('#0d9488', $clinicVars['--cp-primary']);
        $this->assertArrayNotHasKey('--nursery-primary', $clinicVars);
        $this->assertSame('#0f766e', $nurseryVars['--nursery-primary']);
        $this->assertArrayNotHasKey('--clinic-primary', $nurseryVars);
        $this->assertContains($clinicVars['--clinic-on-primary'], ['#ffffff', '#1c1917']);
    }

    #[Test]
    public function it_uses_niche_defaults_when_colors_are_invalid(): void
    {
        $vars = app(TenantThemeService::class)->cssVariables('bad', null, '#2563eb', '#dbeafe');

        $this->assertSame('#2563eb', $vars['--tenant-primary']);
        $this->assertSame('#dbeafe', $vars['--tenant-secondary']);
    }

    #[Test]
    public function nursery_config_defaults_are_teal_mint(): void
    {
        $pair = config('tenant.branding.defaults.nurseries');

        $this->assertSame('#0F766E', $pair['primary']);
        $this->assertSame('#F0FDFA', $pair['secondary']);
        $this->assertSame('#0F766E', TenantThemeService::DEFAULT_PRIMARY);
        $this->assertSame('#F0FDFA', TenantThemeService::DEFAULT_SECONDARY);
    }
}
