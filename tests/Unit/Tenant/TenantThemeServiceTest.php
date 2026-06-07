<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Services\Tenant\TenantThemeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantThemeServiceTest extends TestCase
{
    #[Test]
    public function it_builds_css_variables_with_clinic_and_nursery_aliases(): void
    {
        $vars = app(TenantThemeService::class)->cssVariables('#0d9488', '#ccfbf1');

        $this->assertSame('#0d9488', $vars['--clinic-primary']);
        $this->assertSame('#0d9488', $vars['--nursery-primary']);
        $this->assertSame('#0d9488', $vars['--cp-primary']);
        $this->assertContains($vars['--clinic-on-primary'], ['#ffffff', '#1c1917']);
    }

    #[Test]
    public function it_uses_niche_defaults_when_colors_are_invalid(): void
    {
        $vars = app(TenantThemeService::class)->cssVariables('bad', null, '#2563eb', '#dbeafe');

        $this->assertSame('#2563eb', $vars['--tenant-primary']);
        $this->assertSame('#dbeafe', $vars['--tenant-secondary']);
    }
}
