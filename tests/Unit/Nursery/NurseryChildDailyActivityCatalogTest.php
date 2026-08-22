<?php

declare(strict_types=1);

namespace Tests\Unit\Nursery;

use App\Support\NurseryChildDailyActivityCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NurseryChildDailyActivityCatalogTest extends TestCase
{
    #[Test]
    public function catalog_keys_match_config_and_reject_unknown_types(): void
    {
        $this->assertSame(
            ['meal', 'nap', 'diaper', 'toilet', 'mood', 'activity', 'medication', 'note'],
            NurseryChildDailyActivityCatalog::keys(),
        );
        $this->assertTrue(NurseryChildDailyActivityCatalog::isValidType('meal'));
        $this->assertTrue(NurseryChildDailyActivityCatalog::isValidType('medication'));
        $this->assertFalse(NurseryChildDailyActivityCatalog::isValidType('storytime'));
        $this->assertTrue(NurseryChildDailyActivityCatalog::defaultParentVisible('meal'));
        $this->assertTrue(NurseryChildDailyActivityCatalog::defaultParentVisible('medication'));
        $this->assertFalse(NurseryChildDailyActivityCatalog::defaultParentVisible('note'));
    }
}
